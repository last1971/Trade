<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetailStoreReturn extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'BACKSHOPCODE';

    protected $table = 'BACKSHOP';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }
}
