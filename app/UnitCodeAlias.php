<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitCodeAlias extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = ['unit_code_id', 'name'];

    public function unitCode()
    {
        return $this->belongsTo(UnitCode::class);
    }

    public static function rmember(string $alias)
    {
        return \Cache::remember(
            'UnitCodeAlias=' . $alias,
            6000,
            fn() => UnitCodeAlias::query()
                ->with('unitCode')
                ->firstWhere('name', $alias),
        );
    }
}
