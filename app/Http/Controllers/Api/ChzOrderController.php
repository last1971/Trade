<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Marking\ChzOrderService;
use Illuminate\Http\Request;

/**
 * Прокси карточки товара к chz-сервису: заказы КМ по GTIN, заказ, статус, этикетки, коды.
 * Логики нет — адрес/ключ/ИНН/ошибки в ChzClient и ChzOrderService.
 */
class ChzOrderController extends Controller
{
    private const PDF_CHUNK = 10;

    public function __construct(private ChzOrderService $service)
    {
    }

    public function ordersByGtin(string $gtin)
    {
        return $this->service->ordersByGtin($gtin);
    }

    public function order(Request $request, string $gtin)
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:150000'],
            ['quantity.required' => 'Укажите количество кодов', 'quantity.min' => 'Минимум 1 код']);
        return $this->service->order($gtin, intval($request->quantity), $request->header('X-Request-Id'));
    }

    public function status(string $orderId)
    {
        return $this->service->status($orderId);
    }

    public function pdfList(string $orderId)
    {
        return $this->service->pdfList($orderId, self::PDF_CHUNK);
    }

    public function pdfChunk(string $orderId, int $n)
    {
        $response = $this->service->pdfChunk($orderId, $n, self::PDF_CHUNK);
        $disposition = $response->getHeaderLine('Content-Disposition');
        return response((string)$response->getBody(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition ?: "attachment; filename=order_{$orderId}_{$n}.pdf",
        ]);
    }

    public function codesCsv(string $orderId)
    {
        $csv = ChzOrderService::codesCsv($this->service->codes($orderId));
        return response()->streamDownload(fn() => print($csv), "codes_{$orderId}.csv",
            ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
