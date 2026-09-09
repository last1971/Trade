<?php

namespace App\Http\Controllers\Api;

use App\ChzBatch;
use App\Http\Controllers\Controller;
use App\Invoice;
use Illuminate\Support\Facades\DB;
use App\Services\Marking\ChzOutboxService;

/**
 * Страница «Отправка в ЧЗ»: что уехало в Честный знак, что ждёт, что отбито.
 * Своего журнала нет — читаем те же пачки, которыми живёт воркер.
 */
class ChzOutboxController extends Controller
{
    private const LIMIT = 100;

    public function __construct(private ChzOutboxService $service)
    {
    }

    public function index()
    {
        $batches = ChzBatch::whereNotNull('STATUS')->orderBy('ID', 'desc')->limit(self::LIMIT)->get();
        // Человеку нужен номер счёта, а не его внутренний код: одним запросом
        // на весь список, ссылку на страницу счёта построит фронт по SCODE.
        $numbers = Invoice::whereIn('SCODE', $batches->pluck('SCODE')->filter()->unique())
            ->pluck('NS', 'SCODE');
        return [
            'enabled' => $this->service->enabled(),
            'batches' => $batches->map(fn(ChzBatch $b) => [
                'id' => $b->ID,
                'kind' => trim((string)$b->KIND),
                'status' => trim((string)$b->STATUS),
                'cnt' => $b->CNT,
                'scode' => $b->SCODE,
                'invoiceNumber' => $b->SCODE ? ($numbers[$b->SCODE] ?? null) : null,
                'parentId' => $b->PARENT_ID,
                'reportId' => $b->REPORT_ID,
                'docUuid' => $b->DOC_UUID,
                'errorText' => $b->ERROR_TEXT,
                'createdBy' => trim((string)$b->CREATED_BY),
                'createdAt' => $b->CREATED_AT,
                'sentAt' => $b->SENT_AT,
                'confirmedAt' => $b->CONFIRMED_AT,
            ]),
        ];
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
            'select k.KI, m.GOODSCODE, nm.NAME, m.QUANTITY from CHZ_BATCH_KI k '
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
                'goodscode' => $row->GOODSCODE === null ? null : intval($row->GOODSCODE),
                'name' => $row->NAME === null ? null : trim((string)$row->NAME),
                'quantity' => $row->QUANTITY === null ? null : intval($row->QUANTITY),
            ], $rows),
        ];
    }

    /**
     * Повтор отбитой пачки: не переиспользуем её номер (он же идентификатор
     * запроса в сервисе), а создаём новую с тем же списком кодов.
     */
    public function retry(int $id)
    {
        return $this->service->retry(ChzBatch::findOrFail($id));
    }
}
