<?php


namespace App\Services;


use App\Invoice;
use Illuminate\Database\Eloquent\Builder;

class InvoiceService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Invoice::class);
        $this->agregateAttributes = [
            'invoiceLinesCount' => ['invoiceLines' => function (Builder $query) {
                $query->invoiceLinesCount();
            }],
            'invoiceLinesSum' => ['invoiceLines' => function (Builder $query) {
                $query->invoiceLinesSum();
            }],
        ];
        $this->dateAttributes = ['DATA'];
    }
}
