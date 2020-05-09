<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class OrderStep extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = [
        'GOODSCODE',
        'BOUND_QUAN_SKLAD',
        'BOUND_QAUN_SHOP',
        'QUAN_TO_ZAKAZ_SKLAD',
        'QUAN_TO_ZAKAZ_SHOP',
        'USERNAME',
        'DATA'
    ];

    protected $primaryKey = 'ID';

    protected $sequenceName = 'GEN_BOUND_QUAN_ID';

    protected $table = 'BOUND_QUAN';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

}
