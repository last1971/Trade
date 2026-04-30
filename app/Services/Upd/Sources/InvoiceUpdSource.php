<?php

namespace App\Services\Upd\Sources;

use App\Buyer;
use App\Exceptions\ApiException;
use App\Firm;
use App\FirmHistory;
use App\Invoice;
use App\InvoiceLine;
use App\Services\Upd\Contracts\UpdException;
use App\Services\Upd\Contracts\UpdLineDto;
use App\Services\Upd\Contracts\UpdSourceInterface;
use App\TransferOut;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvoiceUpdSource implements UpdSourceInterface
{
    private Invoice $invoice;
    private ?string $basis;
    private ?string $basisNumber;
    private ?string $basisDate;
    private array $advanceInvoices;
    private ?Collection $cachedLines = null;

    public function __construct(
        Invoice $invoice,
        ?string $basis = null,
        ?string $basisNumber = null,
        ?string $basisDate = null,
        array $advanceInvoices = []
    ) {
        $this->invoice = $invoice;
        $this->basis = $basis;
        $this->basisNumber = $basisNumber;
        $this->basisDate = $basisDate;
        $this->advanceInvoices = $advanceInvoices;

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

    public function getFileId(): string
    {
        return 'ON_NSCHFDOPPR_'
            . ($this->invoice->buyer->advancedBuyer->edo_id ?? $this->invoice->buyer->Inn)
            . '_' . $this->invoice->firm->EDOID
            . '_' . Carbon::now()->format('Ymd')
            . '-' . Str::uuid();
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

    public function getBasis(): ?string
    {
        return $this->basis;
    }

    public function getBasisNumber(): ?string
    {
        return $this->basisNumber;
    }

    public function getBasisDate(): ?string
    {
        return $this->basisDate;
    }

    public function getCashFlows(): Collection
    {
        $cashFlows = $this->invoice->cashFlows->filter(fn($v) => !$v->SFCODE1);
        foreach ($cashFlows as $cf) {
            if (empty($cf->NPP)) {
                throw new ApiException('ВМС не занес обязательный номер платежного поручения!', 400);
            }
        }
        return $cashFlows;
    }

    public function getAdvanceInvoices(): array
    {
        return $this->advanceInvoices;
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
            goodsCode: $line->GOODSCODE,
            countryNumCode: null,
            strana: null,
            gtdNumber: null,
            markCodes: $line->markCodes,
        ));
    }
}
