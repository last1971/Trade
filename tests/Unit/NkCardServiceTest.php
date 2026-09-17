<?php

namespace Tests\Unit;

use App\GoodClassif;
use App\Services\Marking\NkCardService;
use PHPUnit\Framework\TestCase;

class NkCardServiceTest extends TestCase
{
    public function testOwnershipBySupplierInn()
    {
        $this->assertSame(NkCardService::SUPPLIER, NkCardService::ownership(new GoodClassif(['SUPPLIER_INN' => '7700000001'])));
        $this->assertSame(NkCardService::OWN, NkCardService::ownership(new GoodClassif(['SUPPLIER_INN' => '  '])));
        $this->assertSame(NkCardService::OWN, NkCardService::ownership(new GoodClassif([])));
    }

    public function testStateFromCardPrefersDetailedStatus()
    {
        $this->assertSame('moderation', NkCardService::stateFromCard(['good_status' => 'draft', 'good_detailed_status' => ['moderation']])['state']);
        $this->assertSame('notsigned', NkCardService::stateFromCard(['good_status' => 'draft', 'good_detailed_status' => ['notsigned']])['state']);
        $this->assertSame('published', NkCardService::stateFromCard(['good_status' => 'published', 'good_detailed_status' => ['published']])['state']);
        $this->assertSame('draft', NkCardService::stateFromCard(['good_status' => 'draft', 'good_detailed_status' => ['draft']])['state']);
        // Незнакомый статус не должен уронить CHECK в базе
        $this->assertSame('draft', NkCardService::stateFromCard(['good_status' => 'weird'])['state']);
    }

    public function testStateFromCardKeepsModerationText()
    {
        $snap = NkCardService::stateFromCard(['good_status' => 'errors', 'moderation_errors' => 'Неверный вид товара']);
        $this->assertSame('errors', $snap['state']);
        $this->assertSame('Неверный вид товара', $snap['text']);
        $this->assertNull(NkCardService::stateFromCard(['good_status' => 'published'])['text']);
    }

    public function testParseFeedRejectedCollectsReasons()
    {
        $feed = NkCardService::parseFeed(['status' => 'Rejected', 'result' => ['0' => ['Не заполнено значение Вид товара']]]);
        $this->assertTrue($feed['done']);
        $this->assertTrue($feed['rejected']);
        $this->assertStringContainsString('Вид товара', $feed['text']);
    }

    public function testParseFeedDoneAndPending()
    {
        $done = NkCardService::parseFeed(['status' => 'Received', 'item' => [['gtin' => '0400003998340', 'good_id' => 1164309573]]]);
        $this->assertSame(['done' => true, 'gtin' => '0400003998340', 'goodId' => 1164309573, 'rejected' => false, 'text' => null], $done);
        $this->assertFalse(NkCardService::parseFeed(['status' => 'Processing'])['done']);
    }

    public function testGtin14AddsLeadingZeroToCatalogCode()
    {
        $this->assertSame('00400003998340', NkCardService::gtin14('0400003998340'));
        $this->assertSame('00400003998340', NkCardService::gtin14(' 00400003998340 '));
    }
}
