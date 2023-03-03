<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitCode extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function aliases()
    {
        return $this->hasMany(UnitCodeAlias::class);
    }
}
