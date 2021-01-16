<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static Builder requestBuilder()
 */
class PaymentOrder extends Model
{
    use HasFactory;

    protected $fillable = ['payment_id', 'amount', 'date', 'number'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
