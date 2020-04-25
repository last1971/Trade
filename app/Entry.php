<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'PR_META';

    public function shopLine()
    {
        return $this->belongsTo('App\ShopLine', 'SHOPINCODE', 'SHOPINCODE');
    }

    public function storeLine()
    {
        return $this->belongsTo('App\StoreLine', 'SKLADINCODE', 'SKLADINCODE');
    }

}
