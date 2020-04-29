<?php

namespace App;

use App\ModelTraits\InnKppTrait;
use Illuminate\Database\Eloquent\Model;

class Firm extends Model
{
    use InnKppTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'FIRM_ID';

    protected $table = 'FIRMS';

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'FIRM_ID', 'FIRM_ID');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'FIRM_ID', 'FIRM_ID');
    }

}
