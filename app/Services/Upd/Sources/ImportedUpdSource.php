<?php

namespace App\Services\Upd\Sources;

use App\Buyer;
use App\Firm;
use App\FirmHistory;
use App\Invoice;
use App\Services\Upd\Contracts\UpdSourceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Источник для импорта УПД из внешних форматов (1С Excel, уведомления Wildberries и т.п.).
 * Простой data-контейнер — caller передаёт уже разобранные данные через конструктор.
 */
class ImportedUpdSource implements UpdSourceInterface
{
    private Firm $firm;
    private ?FirmHistory $firmHistory;
    private Buyer $buyer;
    private string $documentNumber;
    private Carbon $documentDate;
    private string $fileId;
    private Collection $lines;
    private array $advanceInvoices;
    private ?string $basis;
    private ?string $basisNumber;
    private ?string $basisDate;

    public function __construct(
        Firm $firm,
        Buyer $buyer,
        string $documentNumber,
        Carbon $documentDate,
        string $fileId,
        Collection $lines,
        array $advanceInvoices = [],
        ?string $basis = null,
        ?string $basisNumber = null,
        ?string $basisDate = null,
        ?FirmHistory $firmHistory = null
    ) {
        $this->firm = $firm;
        $this->buyer = $buyer;
        $this->documentNumber = $documentNumber;
        $this->documentDate = $documentDate;
        $this->fileId = $fileId;
        $this->lines = $lines;
        $this->advanceInvoices = $advanceInvoices;
        $this->basis = $basis;
        $this->basisNumber = $basisNumber;
        $this->basisDate = $basisDate;
        $this->firmHistory = $firmHistory;
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
        return $this->documentNumber;
    }

    public function getDocumentDate(): Carbon
    {
        return $this->documentDate;
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function getFirm(): Firm
    {
        return $this->firm;
    }

    public function getFirmHistory(): ?FirmHistory
    {
        return $this->firmHistory;
    }

    public function getBuyer(): Buyer
    {
        return $this->buyer;
    }

    public function getInvoice(): ?Invoice
    {
        return null;
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
        return new Collection();
    }

    public function getAdvanceInvoices(): array
    {
        return $this->advanceInvoices;
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }
}
