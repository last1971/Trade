<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdvancedBuyer extends Model
{
    //
    protected $connection = 'mysql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'buyer_id', 'edo_id', 'consignee', 'consigneeAddress'
    ];

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'buyer_id', 'POKUPATCODE',);
    }
}
