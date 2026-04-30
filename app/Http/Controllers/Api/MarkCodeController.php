<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Invoice;
use App\Services\Marking\MarkCodeTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarkCodeController extends Controller
{
    public function markAsTransferred(Request $request, MarkCodeTransferService $service)
    {
        $data = $request->validate([
            'invoice_id' => 'required|integer',
            'transfer_type' => ['required', Rule::in([1, 2, 3])],
            'retire_reason' => ['required', Rule::in([1, 2, 3, 4, 5, 6])],
        ]);

        $invoice = Invoice::with('invoiceLines')->findOrFail($data['invoice_id']);
        $count = $service->markAsTransferred($invoice, $data['transfer_type'], $data['retire_reason']);

        return ['count' => $count];
    }

    public function unmarkAsTransferred(Request $request, MarkCodeTransferService $service)
    {
        $data = $request->validate([
            'invoice_id' => 'required|integer',
        ]);

        $invoice = Invoice::with('invoiceLines')->findOrFail($data['invoice_id']);
        $count = $service->unmarkAsTransferred($invoice);

        return ['count' => $count];
    }
}
