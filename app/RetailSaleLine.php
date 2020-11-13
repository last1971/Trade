<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetailSaleLine extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SHOPLOGCODE';

    protected $table = 'SHOPLOG';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }
}
