<?php

namespace App\Interfaces;

/**
 * Документ, по которому коды маркировки уезжают покупателю.
 *
 * Одна точка правды по ручной пометке «коды переданы»: ни контроллер, ни
 * MarkCodeTransferService не знают, счёт перед ними или УПД. Документ сам
 * говорит, какие у него коды, кто покупатель и каким видом передачи это
 * оформляется в MARKCODES.
 */
interface IMarkCodeDocument
{
    /**
     * Все коды маркировки документа, включая уже переданные.
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function markCodes();

    /**
     * Покупатель документа — по нему решается, нужна ли пометка вообще.
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function buyer();

    /** Имя документа для сообщений об ошибках: «счёт № 15574». */
    public function markCodeDocumentTitle(): string;

    /** MARKCODES.TRANSFER_TYPE: 1=УПД юрлицу, 2=УПД-2 маркетплейсу (FBO). */
    public function markCodeTransferType(): int;

    /** MARKCODES.RETIRE_REASON: 1=продажа, 3=передача B2B/FBO. */
    public function markCodeRetireReason(): int;

    /**
     * Почему по этому документу коды помечать нельзя, или null если можно.
     * Счёт отпадает, как только по нему сделана УПД: дальше документ передачи —
     * она, а УПД-2 со счёта остаётся только маркетплейсам.
     */
    public function markCodeTransferBlockReason(): ?string;
}
