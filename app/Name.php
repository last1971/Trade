<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'NAMECODE';

    protected $table = 'NAME';

    public function category()
    {
        return $this->belongsTo('App\Category', 'CATEGORYCODE', 'CATEGORYCODE');
    }
}
