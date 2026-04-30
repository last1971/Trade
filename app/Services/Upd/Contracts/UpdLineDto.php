<?php

namespace App\Services\Upd\Contracts;

use Illuminate\Support\Collection;

class UpdLineDto
{
    public Collection $markCodes;

    public function __construct(
        public string $name,
        public int $quantity,
        public string $price,
        public string $amount,
        public string $amountWithoutVat,
        public ?string $unitCode,
        public ?string $unitName,
        public string $goodsCode,
        public ?string $countryNumCode = null,
        public ?string $strana = null,
        public ?string $gtdNumber = null,
        ?Collection $markCodes = null
    ) {
        $this->markCodes = $markCodes ?? new Collection();
    }
}
