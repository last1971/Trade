<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static Builder requestBuilder()
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'seller_id', 'number', 'date', 'amount', 'weight', 'pay_before', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'WHEREISPOSTCODE');
    }
}
