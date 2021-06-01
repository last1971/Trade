<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fifo extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'FIFO_T';

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'PR_META_IN_ID', 'ID');
    }

}
