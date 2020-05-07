<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['NAME', 'SERIA', 'CATEGORYCODE'];

    protected $primaryKey = 'NAMECODE';

    protected $sequenceName = 'NAMECODE_GEN';

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
