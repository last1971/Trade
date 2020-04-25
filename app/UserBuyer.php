<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserBuyer extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'mysql';

    protected $fillable = ['user_id', 'buyer_id'];

    public function users()
    {
        return $this->belongsTo('App\User');
    }

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'buyer_id', 'POKUPATCODE');
    }
}
