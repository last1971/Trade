<?php

namespace App\Services\Edo;

use App\Buyer;
use App\Services\Edo\Contracts\EdoException;
use App\Services\Edo\Contracts\EdoProviderInterface;
use App\Services\Edo\Providers\DiadocProvider;
use App\Services\Edo\Providers\SbisProvider;

class EdoProviderFactory
{
    private SbisProvider $sbis;
    private DiadocProvider $diadoc;

    public function __construct(SbisProvider $sbis, DiadocProvider $diadoc)
    {
        $this->sbis = $sbis;
        $this->diadoc = $diadoc;
    }

    public function forBuyer(Buyer $buyer): EdoProviderInterface
    {
        $code = $buyer->advancedBuyer->edo_provider ?? 'sbis';
        return $this->byCode($code);
    }

    public function byCode(string $code): EdoProviderInterface
    {
        switch ($code) {
            case 'sbis':
                return $this->sbis;
            case 'diadoc':
                return $this->diadoc;
            default:
                throw new EdoException("Unknown EDO provider: {$code}");
        }
    }

    public function available(): array
    {
        return [
            'sbis' => 'СБИС',
            'diadoc' => 'Диадок',
        ];
    }
}
