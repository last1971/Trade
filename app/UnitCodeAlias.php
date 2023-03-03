<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitCodeAlias extends Model
{
    use HasFactory;

    protected $fillable = ['unit_code_id', 'name'];

    public function unitCode()
    {
        return $this->belongsTo(UnitCode::class);
    }
}
