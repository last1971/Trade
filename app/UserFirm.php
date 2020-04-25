<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFirm extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'mysql';

    protected $fillable = ['user_id', 'firm_id'];

    public function users()
    {
        return $this->belongsTo('App\User');
    }

    public function firm()
    {
        return $this->belongsTo('App\Firm', 'firm_id', 'FIRM_ID');
    }
}
