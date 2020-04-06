<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'STAFF';

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'STAFF_ID', 'ID');
    }

    public function user()
    {
        return $this->hasOne('App\User', 'employeeId', 'ID');
    }
}
