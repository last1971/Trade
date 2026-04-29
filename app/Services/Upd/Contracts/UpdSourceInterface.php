<?php

namespace App\Services\Upd\Contracts;

use App\Buyer;
use App\Firm;
use App\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface UpdSourceInterface
{
    /**
     * Атрибут <Документ Функция>: 'СЧФДОП' (УПД) | 'ДОП' (УПД-2).
     */
    public function getFunction(): string;

    /**
     * Атрибут <Документ НаимДокОпр>: 'УПД' | 'ДОП'.
     */
    public function getDocumentName(): string;

    /**
     * Атрибут <Документ ПоФактХЖ>: текст «об отгрузке…» или «о передаче…».
     */
    public function getOperationDescription(): string;

    /**
     * Атрибут <СвПер ВидОпер>: 'Продажа' | 'Передача'.
     */
    public function getOperationType(): string;

    public function getDocumentNumber(): string;

    public function getDocumentDate(): Carbon;

    /**
     * Идентификатор файла в формате ON_NSCHFDOPPR_*.
     */
    public function getFileId(): string;

    public function getFirm(): Firm;

    public function getBuyer(): Buyer;

    /**
     * Базовый счёт (для блока ОснПер). Может быть NULL для импорта.
     */
    public function getInvoice(): ?Invoice;

    /**
     * Платёжки для блока СвПрД. Пустая коллекция если нет.
     */
    public function getCashFlows(): Collection;

    /**
     * Авансовые счета-фактуры (АСЧФ) для блока ДопСвФХЖ.
     */
    public function getAdvanceInvoices(): array;

    /**
     * @return Collection<UpdLineDto>
     */
    public function getLines(): Collection;
}
