<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use VAT;

class TransferOutLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'REALPRICEFCODE';

    public static $snakeAttributes = false;

    protected $table = 'REALPRICEF';

    public function category()
    {
        return $this->hasOneThrough(
            'App\Category',
            'App\Good',
            'GOODSCODE',
            'CATEGORYCODE',
            'GOODSCODE',
            'CATEGORYCODE'
        );
    }

    public function getAmountWithoutVatAttribute()
    {
        return str_replace(
            ',',
            '.',
            round($this->getAttributes()['SUMMAP'] / (100 + VAT::get($this->transferOut->DATA)) * 100, 2)
        );
    }

    public function getPriceWithoutVatAttribute()
    {
        return str_replace(
            ',',
            '.', round($this->amountWithoutVat / $this->QUAN, 2)
        );
    }

    public function getCountryNumCodeAttribute()
    {
        return config('country_codes')[Str::upper($this->STRANA)];
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function invoiceLine()
    {
        return $this->belongsTo('App\InvoiceLine', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function name()
    {
        return $this->hasOneThrough(
            'App\Name',
            'App\Good',
            'GOODSCODE',
            'NAMECODE',
            'GOODSCODE',
            'NAMECODE'
        );
    }

    public function transferOut()
    {
        return $this->belongsTo('App\TransferOut', 'SFCODE', 'SFCODE');
    }
}
