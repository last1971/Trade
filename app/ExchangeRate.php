<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['CharCode', 'date', 'value'];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'CharCode', 'CharCode');
    }
}
