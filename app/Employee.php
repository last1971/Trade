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

    protected $with = ['employeePosition'];

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'STAFF_ID', 'ID');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'STAFF_ID', 'ID');
    }

    public function user()
    {
        return $this->hasOne('App\User', 'employeeId', 'ID');
    }

    public function employeePosition()
    {
        return $this->belongsTo('App\EmployeePosition', 'JOBS_ID', 'ID');
    }
}
