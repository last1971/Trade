<?php

namespace App\ModelTraits;

use Illuminate\Support\Str;

trait InnKppTrait
{
    public function getInnAttribute()
    {
        return Str::before($this->getAttributes()['INN'], '/');
    }

    public function getKppAttribute()
    {
        if (strpos($this->getAttributes()['INN'], '/') === FALSE) return '';
        return Str::after($this->getAttributes()['INN'], '/');
    }
}
