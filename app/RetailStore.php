<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetailStore extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SHOPSKLADCODE';

    protected $table = 'SHOPSKLAD';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }
}
