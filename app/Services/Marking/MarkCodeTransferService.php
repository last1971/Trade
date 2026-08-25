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
        // Без явной DB::transaction — Firebird-драйвер проекта работает с autocommit=0
        // и не любит вложенные транзакции. UPDATE с whereIn атомарен на уровне SQL.
        $codes = $this->codes($document);

        $alreadyTransferred = $codes->where('TRANSFER_TYPE', '!=', 0);
        if ($alreadyTransferred->isNotEmpty()) {
            throw new MarkingException(
                'Коды уже переданы ранее: ' . $alreadyTransferred->pluck('KI')->implode(', ')
            );
        }

        $wrongStatus = $codes->where('STATUS', '!=', 5);
        if ($wrongStatus->isNotEmpty()) {
            throw new MarkingException(
                'Коды не в обороте (STATUS != 5): ' . $wrongStatus->pluck('KI')->implode(', ')
            );
        }

        $transferType = $document->markCodeTransferType();
        $retireReason = $document->markCodeRetireReason();

        $count = MarkCode::whereIn('MARKCODE', $codes->pluck('MARKCODE'))
            ->update([
                'TRANSFER_TYPE' => $transferType,
                'STATUS' => 6,
                'RETIRE_REASON' => $retireReason,
                'RETIRED_AT' => Carbon::now(),
            ]);

        Log::info('MarkCodeTransfer: marked as transferred', [
            'document' => $document->markCodeDocumentTitle(),
            'document_id' => $document->getKey(),
            'transfer_type' => $transferType,
            'retire_reason' => $retireReason,
            'count' => $count,
        ]);

        return $count;
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

        $notTransferred = $codes->where('TRANSFER_TYPE', 0);
        if ($notTransferred->isNotEmpty()) {
            throw new MarkingException(
                'Часть кодов не была передана: ' . $notTransferred->pluck('KI')->implode(', ')
            );
        }

        $count = MarkCode::whereIn('MARKCODE', $codes->pluck('MARKCODE'))
            ->update([
                'TRANSFER_TYPE' => 0,
                'STATUS' => 5,
                'RETIRE_REASON' => null,
                'RETIRED_AT' => null,
            ]);

        Log::info('MarkCodeTransfer: unmarked as transferred', [
            'document' => $document->markCodeDocumentTitle(),
            'document_id' => $document->getKey(),
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Коды документа + общие проверки, до которых нет смысла что-то делать.
     *
     * @throws MarkingException
     */
    private function codes(IMarkCodeDocument $document): Collection
    {
        $title = $document->markCodeDocumentTitle();

        // Не участнику ЧЗ коды в УПД не уезжают вовсе: их выводит из оборота
        // MARKCODES_BIND_TO_SF при создании УПД, руками помечать нечего.
        if (!optional($document->buyer)->transfersMarkCodes()) {
            throw new MarkingException(
                "Покупатель не работает с ЧЗ ({$title}): коды выводятся из оборота при создании УПД, "
                . 'ручная пометка не нужна.'
            );
        }

        $codes = $document->markCodes()->get();

        if ($codes->isEmpty()) {
            throw new MarkingException("Нет привязанных кодов маркировки ({$title})");
        }

        return $codes;
    }
}
