<?php

namespace App\Services\Upd;

use App\Invoice;
use App\Services\Upd\Contracts\UpdException;
use App\Services\Upd\Contracts\UpdSourceInterface;
use App\Services\Upd\Sources\InvoiceUpdSource;
use App\Services\Upd\Sources\TransferOutUpdSource;
use App\TransferOut;

class UpdSourceFactory
{
    /**
     * Создаёт UpdSourceInterface на основе $request.
     *
     * Параметры запроса:
     * - type: 'upd' (по умолчанию) | 'upd2'
     * - transferOut: ID или объект TransferOut (для type=upd)
     * - invoice_id: ID счёта (для type=upd2)
     * - basis, basisNumber, basisDate: переопределение основания передачи
     * - advanceInvoices: JSON-строка с авансовыми СФ
     */
    public function fromRequest($request): UpdSourceInterface
    {
        $type = $request->get('type', 'upd');
        $basis = $request->get('basis');
        $basisNumber = $request->get('basisNumber');
        $basisDate = $request->get('basisDate');
        $advanceInvoices = json_decode($request->get('advanceInvoices'), true) ?? [];

        if ($type === 'upd2') {
            $invoiceId = $request->get('invoice_id');
            if (!$invoiceId) {
                throw new UpdException('Параметр invoice_id обязателен для type=upd2');
            }
            $invoice = Invoice::with([
                'firm',
                'firmHistory',
                'buyer.advancedBuyer',
                'cashFlows',
            ])->find($invoiceId);
            if (!$invoice) {
                throw new UpdException("Invoice {$invoiceId} не найден");
            }
            return new InvoiceUpdSource($invoice, $basis, $basisNumber, $basisDate, $advanceInvoices);
        }

        if ($type === 'upd') {
            $transferOut = is_object($request->get('transferOut'))
                ? $request->get('transferOut')
                : TransferOut::with([
                    'firm',
                    'firmHistory',
                    'buyer.advancedBuyer',
                    'invoice.cashFlows',
                ])->find($request->get('transferOut'));
            if (!$transferOut) {
                throw new UpdException('TransferOut не найден');
            }
            return new TransferOutUpdSource($transferOut, $basis, $basisNumber, $basisDate, $advanceInvoices);
        }

        throw new UpdException("Unknown UPD type: {$type}");
    }
}
