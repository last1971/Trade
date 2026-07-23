<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Предложение авто-классификации на проверку (стейджинг для страницы-ревью).
 * MySQL; наполняется фоновой пачкой, применяется через GoodClassifyService.
 */
class TnvedSuggestion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'goodscode' => 'integer',
        'mark_required' => 'integer',
        'confidence' => 'integer',
    ];
}
