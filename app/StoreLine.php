<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StoreLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SKLADINCODE';

    protected $table = 'SKLADIN';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function orderLine()
    {
        return $this->belongsTo('App\OrderLine', 'ZAKAZ_DETAIL_ID', 'ID');
    }

    public function entry()
    {
        return $this->belongsTo('App\Entry', 'SKLADINCODE', 'SKLADINCODE');
    }

    public function fifos()
    {
        return $this->hasManyThrough(
            Fifo::class,
            Entry::class,
            'SKLADINCODE',
            'PR_META_IN_ID',
            'SKLADINCODE',
            'ID'
        );
    }
}
