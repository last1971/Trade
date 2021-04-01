<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerGood extends Model
{
    use HasFactory;

    public function setNameAttribute(string $value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['search_name'] = mb_ereg_replace(
            config('app.search_replace'), '', $value
        );
    }
/*
    public function seller()
    {
        return $this->belongsTo('App\Seller', 'WHEREISPOSTCODE', 'seller_id');
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'good_id');
    }
*/
}
