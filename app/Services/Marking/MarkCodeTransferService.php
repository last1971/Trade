<?php

namespace App\Services\Marking;

use App\Interfaces\IMarkCodeDocument;
use App\MarkCode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Ручная пометка кодов маркировки как переданных покупателю.
 *
 * Применяется, когда XML отдали в ЭДО (или ЛК маркетплейса) мимо системы:
 * сама отправка ничего в MARKCODES не пишет, а коды после неё уже не наши.
 *
 * Работает с любым документом-носителем кодов ({@see IMarkCodeDocument}):
 * счёт (УПД-2 маркетплейсу) и УПД (юрлицу) ходят одним кодом, вид передачи
 * и причину вывода называет сам документ.
 */
class MarkCodeTransferService
{
    /**
     * Помечает все коды документа как переданные.
     *
     * @return int количество обновлённых кодов
     * @throws MarkingException
     */
    public function markAsTransferred(IMarkCodeDocument $document): int
    {
        $codes = $this->codes($document);

        $this->reject($codes->where('TRANSFER_TYPE', '!=', 0), 'Коды уже переданы ранее');
        $this->reject($codes->where('STATUS', '!=', 5), 'Коды не в обороте (STATUS != 5)');

        return $this->apply($document, $codes, 'marked as transferred', [
            'TRANSFER_TYPE' => $document->markCodeTransferType(),
            'STATUS' => 6,
            'RETIRE_REASON' => $document->markCodeRetireReason(),
            'RETIRED_AT' => Carbon::now(),
        ]);
    }

    /**
     * Откатывает передачу кодов документа.
     * Применяется когда отправка в ЭДО была отменена/отклонена.
     *
     * @return int количество обновлённых кодов
     * @throws MarkingException
     */
    public function unmarkAsTransferred(IMarkCodeDocument $document): int
    {
        $codes = $this->codes($document);

        $this->reject($codes->where('TRANSFER_TYPE', 0), 'Часть кодов не была передана');

        return $this->apply($document, $codes, 'unmarked as transferred', [
            'TRANSFER_TYPE' => 0,
            'STATUS' => 5,
            'RETIRE_REASON' => null,
            'RETIRED_AT' => null,
        ]);
    }

    /**
     * Пометить после успешной отправки документа в ЭДО.
     * Молча пропускает документы, где помечать нечего (покупатель не работает
     * с ЧЗ, кодов нет, уже помечено) — отправка не должна падать из-за этого.
     *
     * @return int количество помеченных кодов
     */
    public function markAfterSend(IMarkCodeDocument $document): int
    {
        $state = $this->state($document);

        if (!$state['available'] || $state['transferred'] > 0) {
            return 0;
        }

        return $this->markAsTransferred($document);
    }

    /**
     * Состояние пометки по документу — на нём же строятся кнопки в карточке:
     * available=false → показывать нечего, transferred=0 → можно помечать,
     * transferred=total → можно только откатывать.
     */
    public function state(IMarkCodeDocument $document): array
    {
        [$reason, $codes] = $this->resolve($document);

        return [
            'available' => $reason === null,
            'reason' => $reason,
            'total' => $codes->count(),
            'transferred' => $codes->where('TRANSFER_TYPE', '!=', 0)->count(),
        ];
    }

    /**
     * Коды документа и причина, по которой трогать их нельзя — одна проверка
     * на все входы: и на кнопки (state), и на сами пометку/откат (codes).
     *
     * @return array{0: string|null, 1: Collection}
     */
    private function resolve(IMarkCodeDocument $document): array
    {
        $title = $document->markCodeDocumentTitle();
        $empty = new Collection();

        // Не участнику ЧЗ коды в УПД не уезжают вовсе: их выводит из оборота
        // MARKCODES_BIND_TO_SF при создании УПД, руками помечать нечего.
        if (!optional($document->buyer)->transfersMarkCodes()) {
            return [
                "Покупатель не работает с ЧЗ ({$title}): коды выводятся из оборота при создании УПД, "
                . 'ручная пометка не нужна.',
                $empty,
            ];
        }

        $blocked = $document->markCodeTransferBlockReason();
        if ($blocked !== null) {
            return [$blocked, $empty];
        }

        $codes = $document->markCodes()->get();

        return $codes->isEmpty()
            ? ["Нет привязанных кодов маркировки ({$title})", $codes]
            : [null, $codes];
    }

    /**
     * @throws MarkingException
     */
    private function codes(IMarkCodeDocument $document): Collection
    {
        [$reason, $codes] = $this->resolve($document);

        if ($reason !== null) {
            throw new MarkingException($reason);
        }

        return $codes;
    }

    /**
     * Отказ, если выборка не пуста: во всех проверках текст одинаковый —
     * причина плюс перечисление КИ.
     *
     * @throws MarkingException
     */
    private function reject(Collection $codes, string $reason): void
    {
        if ($codes->isNotEmpty()) {
            throw new MarkingException($reason . ': ' . $codes->pluck('KI')->implode(', '));
        }
    }

    /**
     * Единственное место, где состояние кодов действительно меняется.
     *
     * Без явной DB::transaction — Firebird-драйвер проекта работает с autocommit=0
     * и не любит вложенные транзакции. UPDATE с whereIn атомарен на уровне SQL.
     */
    private function apply(IMarkCodeDocument $document, Collection $codes, string $what, array $values): int
    {
        $count = MarkCode::whereIn('MARKCODE', $codes->pluck('MARKCODE'))->update($values);

        Log::info("MarkCodeTransfer: {$what}", [
            'document' => $document->markCodeDocumentTitle(),
            'document_id' => $document->getKey(),
            'values' => $values,
            'count' => $count,
        ]);

        return $count;
    }
}
