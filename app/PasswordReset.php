<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    //
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['email', 'token', 'created_at'];
    protected $primaryKey = 'email';

    public function user()
    {
        return $this->belongsTo('App\User', 'email', 'email');
    }
}
