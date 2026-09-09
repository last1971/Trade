<?php

namespace App\Http\Controllers\Api;

use App\ChzBatch;
use App\Services\ChzBatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Marking\ChzOutboxService;

/**
 * Страница «Отправка в ЧЗ»: что уехало в Честный знак, что ждёт, что отбито.
 * Своего журнала нет — читаем те же пачки, которыми живёт воркер.
 */
class ChzOutboxController extends ModelController
{
    private ChzOutboxService $outbox;

    public function __construct(ChzOutboxService $outbox)
    {
        parent::__construct(ChzBatchService::class);
        $this->outbox = $outbox;
    }

    /** Включена ли очередь на этой инсталляции — страница показывает предупреждение. */
    public function state()
    {
        return ['enabled' => $this->outbox->enabled()];
    }


    /**
     * Коды пачки — по кнопке «развернуть», отдельным запросом.
     * Кроме КИ отдаём товар: голый код человеку ничего не говорит,
     * а по названию он проваливается в карточку.
     */
    public function codes(int $id)
    {
        $batch = ChzBatch::findOrFail($id);
        $rows = DB::connection('firebird')->select(
            'select k.KI, m.MARKCODE, m.GOODSCODE, nm.NAME, m.QUANTITY, m.CHZ_SKIP_AT, m.CHZ_SKIP_TEXT from CHZ_BATCH_KI k '
            . 'left join MARKCODES m on m.KI = k.KI '
            . 'left join GOODS g on g.GOODSCODE = m.GOODSCODE '
            . 'left join NAME nm on nm.NAMECODE = g.NAMECODE '
            . 'where k.BATCH_ID = ? order by m.MARKCODE',
            [$batch->ID]
        );
        return [
            'id' => $batch->ID,
            'codes' => array_map(fn($row) => [
                'ki' => trim((string)$row->KI),
                // Ссылка на карточку кода строится по MARKCODE: в КИ есть символы,
                // которые в адресе страницы живут плохо ('/', '?', '%').
                'markcode' => $row->MARKCODE === null ? null : intval($row->MARKCODE),
                'goodscode' => $row->GOODSCODE === null ? null : intval($row->GOODSCODE),
                'name' => $row->NAME === null ? null : trim((string)$row->NAME),
                'quantity' => $row->QUANTITY === null ? null : intval($row->QUANTITY),
                // Снятый с отправки код остаётся в пачке: видно, почему он не уехал.
                'skipAt' => $row->CHZ_SKIP_AT ?? null,
                'skipText' => $row->CHZ_SKIP_TEXT === null ? null : trim((string)$row->CHZ_SKIP_TEXT),
            ], $rows),
        ];
    }

    /**
     * Снять коды с отправки в ЧЗ: весь список пачки либо один код (параметр ki).
     * Нужно, когда Честный знак отказывается работать с кодом — считает чужим
     * или не видит в обороте: без пометки такой код собирался бы в пачку вечно.
     */
    public function skip(int $id, Request $request)
    {
        $batch = ChzBatch::findOrFail($id);
        $ki = trim((string)$request->input('ki', ''));
        $reason = trim((string)$request->input('reason', '')) ?: 'снят вручную';
        $kis = $ki === '' ? $batch->kis() : [$ki];
        return ['skipped' => $this->outbox->skip($kis, $reason)];
    }

    /** Вернуть снятые коды в очередь — та же кнопка, обратное действие. */
    public function unskip(int $id, Request $request)
    {
        $batch = ChzBatch::findOrFail($id);
        $ki = trim((string)$request->input('ki', ''));
        $kis = $ki === '' ? $batch->kis() : [$ki];
        return ['returned' => $this->outbox->unskip($kis)];
    }

    /**
     * Повтор отбитой пачки: не переиспользуем её номер (он же идентификатор
     * запроса в сервисе), а создаём новую с тем же списком кодов.
     */
    public function retry(int $id)
    {
        return $this->outbox->retry(ChzBatch::findOrFail($id));
    }
}
