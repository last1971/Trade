<?php

namespace App\Services\Upd\Sources;

use App\Buyer;
use App\Firm;
use App\FirmHistory;
use App\Invoice;
use App\Services\Upd\Contracts\UpdLineDto;
use App\TransferOut;
use App\TransferOutLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TransferOutUpdSource extends AbstractUpdSource
{
    private TransferOut $transferOut;

    public function __construct(
        TransferOut $transferOut,
        ?string $basis = null,
        ?string $basisNumber = null,
        ?string $basisDate = null,
        array $advanceInvoices = []
    ) {
        parent::__construct($basis, $basisNumber, $basisDate, $advanceInvoices);
        $this->transferOut = $transferOut;
    }

    public function getFunction(): string
    {
        return 'СЧФДОП';
    }

    public function getDocumentName(): string
    {
        return 'УПД';
    }

    public function getOperationDescription(): string
    {
        return 'Документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)';
    }

    public function getOperationType(): string
    {
        return 'Продажа';
    }

    public function getDocumentNumber(): string
    {
        return (string)$this->transferOut->NSF;
    }

    public function getDocumentDate(): Carbon
    {
        return Carbon::create($this->transferOut->DATA);
    }

    public function getFirm(): Firm
    {
        return $this->transferOut->firm;
    }

    public function getFirmHistory(): ?FirmHistory
    {
        return $this->transferOut->firmHistory;
    }

    public function getBuyer(): Buyer
    {
        return $this->transferOut->buyer;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->transferOut->invoice;
    }

    public function getLines(): Collection
    {
        if ($this->cachedLines !== null) {
            return $this->cachedLines;
        }

        $lines = TransferOutLine::with(['category', 'name', 'good', 'markCodes'])
            ->where('SFCODE', '=', $this->transferOut->SFCODE)
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
            countryNumCode: $line->countryNumCode,
            strana: $line->STRANA,
            gtdNumber: $line->GTD,
            markCodes: $this->transferableMarkCodes($line->markCodes),
        ));
    }

    protected function fileIdMarkFlag(): ?bool
    {
        return $this->hasMarkCodes();
    }
}
