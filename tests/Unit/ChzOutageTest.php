<?php

namespace Tests\Unit;

use App\Services\Marking\ChzOutage;
use PHPUnit\Framework\TestCase;

/**
 * Что очередь ЧЗ повторяет сама. Тексты — живые отказы прода 09.09–21.10.2026:
 * сбой связи или сервера ЧЗ — повтор; отказ по существу и всё, где документ мог уйти, — человеку.
 */
class ChzOutageTest extends TestCase
{
    public function outages(): array
    {
        return [
            'тайм-аут сервиса (85127, 19.10)' => ['chz-сервис недоступен: cURL error 28: Operation timed out after 60001 milliseconds with 0 bytes received'],
            'шлюз при входе в СУЗ (82870, 07.10)' => ["chz-сервис: simpleSignIn (suz): {\"error_message\":\"com.fasterxml.jackson.core.JsonParseException: Unrecognized token 'upstream': was expecting (JSON String, Number"],
            '500 вывод (21.10)' => ['chz-сервис: ЧЗ LK_RECEIPT: 500 {"error_message":"Could not open JPA EntityManager for transaction"}'],
            '503 вывод (21.10)' => ['chz-сервис: ЧЗ LK_RECEIPT: 503 "upstream connect error or disconnect/reset before headers. reset reason: connection termination"'],
            '503 ввод' => ['chz-сервис: ЧЗ LP_INTRODUCE_OST: 503 "no healthy upstream"'],
            'DNS СУЗ' => ['chz-сервис: СУЗ DIVISIONMARK: getaddrinfo EAI_AGAIN suzgrid.crpt.ru'],
        ];
    }

    public function refusals(): array
    {
        return [
            'не принадлежит участнику' => ['Честный знак отбил отчёт: ["11: Код идентификации 0100400001518663215,r9/VnfwL7Kf1Mjs-VC не принадлежит участнику оборота."]'],
            'отбил без причины' => ['Честный знак отбил отчёт: без причины'],
            '400 параметр' => ['chz-сервис: ЧЗ ввод в оборот: 400 {"error_message":"Отсутствует обязательный параметр: pg"}'],
            '400 формат КМ' => ['chz-сервис: СУЗ UTILISATION: 400 {"fieldErrors":[{"errorCode":7360,"fieldError":"Неверный формат кода маркировки"}]}'],
            'пустой ответ — документ мог создаться' => ['chz-сервис: ЧЗ LK_RECEIPT: пустой ответ'],
            'нет GS' => ['chz-сервис: в коде нет разделителя GS: прислан КИ без криптохвоста либо разделитель потерян'],
            'ответ не получен' => ['НЕИЗВЕСТНО: chz-сервис: уже отправлен, ответ не получен'],
            'предшественник отбит сбоем — оживит повтор родителя' => ['Предшественник №516 отбит: chz-сервис недоступен: cURL error 28: Operation timed out'],
            'уже повторена' => ['Повторена пачкой №1430. chz-сервис: ЧЗ LK_RECEIPT: 503 "no healthy upstream"'],
            'ручная пометка' => ['Caps Lock при скане: коды исправлены 15.10, повтор'],
            'пусто' => [null],
        ];
    }

    /** @dataProvider outages */
    public function testOutageIsRetried(string $error)
    {
        $this->assertTrue(ChzOutage::is($error));
    }

    /** @dataProvider refusals */
    public function testRefusalGoesToHuman(?string $error)
    {
        $this->assertFalse(ChzOutage::is($error));
    }

    public function testAttemptsAreCountedFromCreatedBy()
    {
        $this->assertSame(0, ChzOutage::attempt('MAGAZIN'));
        $this->assertSame(0, ChzOutage::attempt('web:Ластовка'));
        $this->assertSame(0, ChzOutage::attempt(null));
        $this->assertSame(3, ChzOutage::attempt(ChzOutage::by(3)));
    }

    public function testPauseGrowsAndAttemptsEnd()
    {
        $error = 'chz-сервис: ЧЗ LK_RECEIPT: 503 "no healthy upstream"';
        $this->assertFalse(ChzOutage::due($error, 'MAGAZIN', 14));
        $this->assertTrue(ChzOutage::due($error, 'MAGAZIN', 15));
        $this->assertFalse(ChzOutage::due($error, ChzOutage::by(1), 29));
        $this->assertTrue(ChzOutage::due($error, ChzOutage::by(1), 30));

        $last = ChzOutage::by(ChzOutage::MAX_ATTEMPTS);
        $this->assertFalse(ChzOutage::willRetry($error, $last));
        $this->assertFalse(ChzOutage::due($error, $last, 10000));
        $this->assertTrue(ChzOutage::willRetry($error, ChzOutage::by(ChzOutage::MAX_ATTEMPTS - 1)));
    }

    public function testRefusalIsNeverDue()
    {
        $this->assertFalse(ChzOutage::due('Честный знак отбил отчёт: без причины', 'MAGAZIN', 10000));
    }
}
