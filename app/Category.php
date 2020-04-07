<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'CATEGORYCODE';

    protected $table = 'CATEGORY';

    public function names()
    {
        return $this->hasMany('App\Name', 'CATEGORYCODE', 'CATEGORYCODE');
    }
}
