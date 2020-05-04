<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdvancedSeller extends Model
{
    //
    protected $connection = 'mysql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'seller_id', 'edo_id'
    ];

    public function seller()
    {
        return $this->belongsTo('App\Seller', 'seller_id', 'WHEREISPOSTCODE');
    }
}
