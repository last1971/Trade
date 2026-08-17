<?php

namespace App\Services\Upd\Sources;

use App\Exceptions\ApiException;
use App\Services\Upd\Contracts\UpdLineDto;
use App\Services\Upd\Contracts\UpdSourceInterface;
use App\Services\Upd\UpdFileId;
use Illuminate\Support\Collection;

/**
 * Общая часть источников, собирающих УПД из наших документов (счёт, УПД).
 * Здесь то, что у них одинаково: имя файла, платёжки, основание передачи
 * и правило, кому уходят коды маркировки.
 */
abstract class AbstractUpdSource implements UpdSourceInterface
{
    protected ?Collection $cachedLines = null;

    public function __construct(
        protected ?string $basis = null,
        protected ?string $basisNumber = null,
        protected ?string $basisDate = null,
        protected array $advanceInvoices = []
    ) {
    }

    public function getFileId(): string
    {
        return UpdFileId::build($this->getBuyer(), $this->getFirm(), $this->fileIdMarkFlag());
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

    public function getAdvanceInvoices(): array
    {
        return $this->advanceInvoices;
    }

    public function getCashFlows(): Collection
    {
        $cashFlows = $this->getInvoice()->cashFlows->filter(fn($v) => !$v->SFCODE1);
        foreach ($cashFlows as $cf) {
            if (empty($cf->NPP)) {
                throw new ApiException('ВМС не занес обязательный номер платежного поручения!', 400);
            }
        }
        return $cashFlows;
    }

    /**
     * Признак маркированных товаров для хвоста ИдФайл.
     * NULL — хвост признаков не добавляем.
     */
    protected function fileIdMarkFlag(): ?bool
    {
        return null;
    }

    /**
     * Есть ли в документе хоть один КИЗ. Считаем по уже собранным строкам,
     * чтобы признак в имени файла не разъехался с содержимым файла.
     */
    protected function hasMarkCodes(): bool
    {
        return $this->getLines()->contains(fn(UpdLineDto $line) => $line->markCodes->isNotEmpty());
    }

    /**
     * Коды маркировки уходят в УПД только участникам оборота ЧЗ.
     */
    protected function transferableMarkCodes(Collection $markCodes): Collection
    {
        return $this->getBuyer()->transfersMarkCodes() ? $markCodes : new Collection();
    }
}
