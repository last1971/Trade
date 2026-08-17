<?php

namespace App\Services\Upd\Sources;

use App\Buyer;
use App\Firm;
use App\FirmHistory;
use App\Invoice;
use App\InvoiceLine;
use App\Services\Upd\Contracts\UpdException;
use App\Services\Upd\Contracts\UpdLineDto;
use App\TransferOut;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InvoiceUpdSource extends AbstractUpdSource
{
    private Invoice $invoice;

    public function __construct(
        Invoice $invoice,
        ?string $basis = null,
        ?string $basisNumber = null,
        ?string $basisDate = null,
        array $advanceInvoices = []
    ) {
        parent::__construct($basis, $basisNumber, $basisDate, $advanceInvoices);
        $this->invoice = $invoice;

        if (TransferOut::where('SCODE', $invoice->SCODE)->exists()) {
            throw new UpdException(
                "Для счёта {$invoice->NS} уже сформирован TransferOut — УПД-2 невозможен"
            );
        }
    }

    public function getFunction(): string
    {
        return 'ДОП';
    }

    public function getDocumentName(): string
    {
        return 'ДОП';
    }

    public function getOperationDescription(): string
    {
        return 'Документ о передаче товаров (работ, услуг, имущественных прав) при отгрузке';
    }

    public function getOperationType(): string
    {
        return 'Передача';
    }

    public function getDocumentNumber(): string
    {
        return (string)$this->invoice->NS;
    }

    public function getDocumentDate(): Carbon
    {
        return Carbon::create($this->invoice->DATA);
    }

    public function getFirm(): Firm
    {
        return $this->invoice->firm;
    }

    public function getFirmHistory(): ?FirmHistory
    {
        return $this->invoice->firmHistory;
    }

    public function getBuyer(): Buyer
    {
        return $this->invoice->buyer;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function getLines(): Collection
    {
        if ($this->cachedLines !== null) {
            return $this->cachedLines;
        }

        $lines = InvoiceLine::with(['name', 'good', 'markCodes', 'invoice'])
            ->where('SCODE', '=', $this->invoice->SCODE)
            ->get();

        return $this->cachedLines = $lines->map(fn($line) => new UpdLineDto(
            name: $line->name->NAME,
            quantity: $line->QUAN,
            price: (string)$line->priceWithoutVat,
            amount: (string)$line->SUMMAP,
            amountWithoutVat: (string)$line->amountWithoutVat,
            unitCode: $line->good->unitCode,
            unitName: $line->good->unitName,
            goodsCode: (string)$line->GOODSCODE,
            countryNumCode: null,
            strana: null,
            gtdNumber: null,
            markCodes: $this->transferableMarkCodes($line->markCodes),
        ));
    }
}
