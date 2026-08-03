<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpisReason extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'OSN_SPIS';
}
