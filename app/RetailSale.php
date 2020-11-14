<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetailSale extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'firebird';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'DATATIME';

}
