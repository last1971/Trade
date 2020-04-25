<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\UserOption
 *
 * @property int $id
 * @property int $user_id
 * @property string $option
 * @property array $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static Builder|UserOption newModelQuery()
 * @method static Builder|UserOption newQuery()
 * @method static Builder|UserOption query()
 * @method static Builder|UserOption whereCreatedAt($value)
 * @method static Builder|UserOption whereId($value)
 * @method static Builder|UserOption whereOption($value)
 * @method static Builder|UserOption whereUpdatedAt($value)
 * @method static Builder|UserOption whereUserId($value)
 * @method static Builder|UserOption whereValue($value)
 * @mixin Eloquent
 */
class UserOption extends Model
{
    //
    protected $casts = ['value' => 'array'];

    protected $connection = 'mysql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'option', 'value',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
