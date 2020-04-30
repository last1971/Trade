<?php

namespace App;

use App\ModelTraits\InnKppTrait;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use InnKppTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'WHEREISPOSTCODE';

    protected $table = 'WHEREISPOST';

    public function orders()
    {
        return $this->hasMany('App\Order', 'WHEREISPOSTCODE', 'WHEREISPOSTCODE');
    }
}
