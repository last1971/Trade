<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['NAME', 'SERIA', 'CATEGORYCODE'];

    protected $primaryKey = 'NAMECODE';

    protected $table = 'NAME';

    public function category()
    {
        return $this->belongsTo('App\Category', 'CATEGORYCODE', 'CATEGORYCODE');
    }

    public function goods()
    {
        return $this->hasMany('App\Good', 'NAMECODE', 'NAMECODE');
    }
}
