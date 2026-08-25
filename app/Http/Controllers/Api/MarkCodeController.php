<?php

namespace App\Http\Controllers\Api;

use App\Interfaces\IMarkCodeDocument;
use App\Invoice;
use App\Services\MarkCodeService;
use App\Services\Marking\MarkCodeTransferService;
use App\TransferOut;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarkCodeController extends ModelController
{
    /**
     * Документы, по которым коды маркировки уезжают покупателю.
     * Вид передачи и причина вывода живут в самих моделях, не здесь.
     */
    private const DOCUMENTS = [
        'invoice' => Invoice::class,
        'transfer-out' => TransferOut::class,
    ];

    public function __construct()
    {
        parent::__construct(MarkCodeService::class);
    }

    public function markAsTransferred(Request $request, MarkCodeTransferService $service)
    {
        $count = $service->markAsTransferred($this->document($request));

        return ['count' => $count];
    }

    public function unmarkAsTransferred(Request $request, MarkCodeTransferService $service)
    {
        $count = $service->unmarkAsTransferred($this->document($request));

        return ['count' => $count];
    }

    /** Состояние пометки: какую кнопку показывать в карточке документа. */
    public function transferState(Request $request, MarkCodeTransferService $service)
    {
        return $service->state($this->document($request));
    }

    private function document(Request $request): IMarkCodeDocument
    {
        $data = $request->validate([
            'document' => ['required', Rule::in(array_keys(self::DOCUMENTS))],
            'document_id' => 'required|integer',
        ]);

        $model = self::DOCUMENTS[$data['document']];

        return $model::with('buyer')->findOrFail($data['document_id']);
    }
}
