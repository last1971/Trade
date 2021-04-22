<?php

namespace App;

use App\ModelTraits\InnKppTrait;
use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use InnKppTrait, InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['NAMEPOST', 'EMAIL', 'INN'];

    protected $casts = [
        'IS_API' => 'boolean',
        'IS_FILE' => 'boolean',
    ];

    protected $primaryKey = 'WHEREISPOSTCODE';

    protected $sequenceName = 'WHEREISPOSTCODE_GEN';

    protected $table = 'WHEREISPOST';

    public function orders()
    {
        return $this->hasMany('App\Order', 'WHEREISPOSTCODE', 'WHEREISPOSTCODE');
    }
}
