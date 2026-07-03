<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    public static $snakeAttributes = false;

    protected $connection = 'mysql';

    protected $fillable = [
        'number',
        'type',
        'name',
        'file_path',
        'original_name',
        'mime',
        'size',
        'date_from',
        'date_to',
        'remark',
    ];

    protected $casts = [
        'date_from' => 'date:Y-m-d',
        'date_to' => 'date:Y-m-d',
    ];

    protected $appends = ['is_expired'];

    public function getIsExpiredAttribute()
    {
        return $this->date_to && $this->date_to->lt(today());
    }

    public function certificateGoods()
    {
        return $this->hasMany(CertificateGood::class);
    }

    public function marketplaces()
    {
        return $this->belongsToMany(Marketplace::class)
            ->withPivot('uploaded_at')
            ->withTimestamps();
    }
}
