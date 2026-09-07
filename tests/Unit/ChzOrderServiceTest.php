<?php

namespace Tests\Unit;

use App\Services\Marking\ChzOrderService;
use PHPUnit\Framework\TestCase;

class ChzOrderServiceTest extends TestCase
{
    public function testCodesCsvIsOneKmPerLineWithRawGs()
    {
        $gs = ChzOrderService::GS;
        $km1 = '0100400000000001215AAAAAAAAAAAAAAAAAAAA' . $gs . '91EE12' . $gs . '92abc=';
        $km2 = '0100400000000001215BBBBBBBBBBBBBBBBBBBB' . $gs . '91EE12' . $gs . '92def=';
        $csv = ChzOrderService::codesCsv([
            ['ki' => 'x', 'km' => $km1],
            ['ki' => 'y', 'km' => ''],
            ['ki' => 'z', 'km' => $km2],
        ]);
        $this->assertSame($km1 . "\r\n" . $km2 . "\r\n", $csv);
        $this->assertSame("\r\n", ChzOrderService::codesCsv([]));
    }
}
