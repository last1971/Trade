<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateGood extends Model
{
    use HasFactory;

    public static $snakeAttributes = false;

    protected $connection = 'mysql';

    protected $fillable = ['certificate_id', 'good_id'];

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'good_id', 'GOODSCODE');
    }
}
