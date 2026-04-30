<?php

namespace App\Services\Upd\Sources;

use App\Buyer;
use App\Exceptions\ApiException;
use App\Firm;
use App\FirmHistory;
use App\Invoice;
use App\Services\Upd\Contracts\UpdLineDto;
use App\Services\Upd\Contracts\UpdSourceInterface;
use App\TransferOut;
use App\TransferOutLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransferOutUpdSource implements UpdSourceInterface
{
    private TransferOut $transferOut;
    private ?string $basis;
    private ?string $basisNumber;
    private ?string $basisDate;
    private array $advanceInvoices;
    private ?Collection $cachedLines = null;

    public function __construct(
        TransferOut $transferOut,
        ?string $basis = null,
        ?string $basisNumber = null,
        ?string $basisDate = null,
        array $advanceInvoices = []
    ) {
        $this->transferOut = $transferOut;
        $this->basis = $basis;
        $this->basisNumber = $basisNumber;
        $this->basisDate = $basisDate;
        $this->advanceInvoices = $advanceInvoices;
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

    public function getFileId(): string
    {
        return 'ON_NSCHFDOPPR_'
            . ($this->transferOut->buyer->advancedBuyer->edo_id ?? $this->transferOut->buyer->Inn)
            . '_' . $this->transferOut->firm->EDOID
            . '_' . Carbon::now()->format('Ymd')
            . '-' . Str::uuid();
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
        $cashFlows = $this->transferOut->invoice->cashFlows->filter(fn($v) => !$v->SFCODE1);
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
            markCodes: $line->markCodes,
        ));
    }
}
