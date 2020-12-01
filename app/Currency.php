<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['CharCode', 'NumCode', 'Name'];
    /**
     * @var bool
     */
    public $incrementing = false;
    /**
     * @var string
     */
    protected $primaryKey = 'CharCode';

    public function exchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'CharCode', 'CharCode');
    }

}
