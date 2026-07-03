<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = ['name'];

    public function certificates()
    {
        return $this->belongsToMany(Certificate::class)
            ->withPivot('uploaded_at')
            ->withTimestamps();
    }
}
