<?php

namespace Tests\Unit;

use App\Services\Marking\KmReader;
use PHPUnit\Framework\TestCase;

class KmReaderTest extends TestCase
{
    /** Реальный код из базы: в серийнике есть скобка — её трогать нельзя. */
    private const KI = '0104650676370011215bGGB\'8BB(BLIHTH8eS8';
    private const TAIL = '91EE1292LtCyq5wiekWTYgfznyvhJpk3sSoPDab09SfWDzqeNuo=';

    public function testReadsFlatScan()
    {
        $this->assertSame(self::KI, KmReader::ki(self::KI . self::TAIL));
    }

    public function testReadsScanWithGroupSeparator()
    {
        $gs = "\x1d";
        $scan = self::KI . $gs . '91EE12' . $gs . '92LtCyq5wiekWTYgfznyvhJpk3sSoPDab09SfWDzqeNuo=';

        $this->assertSame(self::KI, KmReader::ki($scan));
    }

    public function testReadsBracketScanAndKeepsBracketInSerial()
    {
        // Режим кладовщика: AI в скобках. Скобка внутри серийника при этом
        // остаётся на месте — снимаются только скобки двух первых применений.
        $scan = '(01)04650676370011(21)5bGGB\'8BB(BLIHTH8eS8(91)EE12(92)LtCyq5wiekWTYgfznyvhJpk3sSoPDab09SfWDzqeNuo=';

        $this->assertSame(self::KI, KmReader::ki($scan));
    }

    public function testReadsPastedCodeWithSpacesAndNewline()
    {
        $this->assertSame(self::KI, KmReader::ki("  " . self::KI . "\r\n"));
    }

    public function testReadsBareKi()
    {
        $this->assertSame(self::KI, KmReader::ki(self::KI));
    }

    public function testRejectsGarbage()
    {
        // Не КМ: нет AI 01 в начале либо строка короче КИ.
        $this->assertSame('', KmReader::ki('какой-то текст'));
        $this->assertSame('', KmReader::ki('010465067637001121'));
        $this->assertSame('', KmReader::ki(''));
    }
}
