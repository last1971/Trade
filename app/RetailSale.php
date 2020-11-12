<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetailSale extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'DATATIME';
}
