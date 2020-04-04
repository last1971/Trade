<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserOption extends Model
{
    //
    protected $casts = ['value' => 'array'];

    protected $connection = 'mysql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'option', 'value',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
