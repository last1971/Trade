<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuichiPrice extends Model
{
    use HasFactory;

    protected $connection = 'ruichi';

    protected $table = 'saleprice';

    public function ruichiGood()
    {
        return $this->belongsTo(RuichiGood::class, 'mainbase');
    }
}
