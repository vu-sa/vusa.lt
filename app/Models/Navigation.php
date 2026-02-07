<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $padalinys_id
 * @property string $name
 * @property string $lang
 * @property string $url
 * @property int $order
 * @property int $is_active
 * @property array<array-key, mixed>|null $extra_attributes column, icon, image, style
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User|null $user
 *
 * @method static \Database\Factories\NavigationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation query()
 *
 * @mixin \Eloquent
 */
class Navigation extends Model
{
    use HasFactory;

    protected $table = 'navigation';

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
        ];
    }

    protected static function booted()
    {
        static::saved(function ($navigation) {
            Cache::tags(['navigation', "locale_{$navigation->lang}"])->flush();
        });

        static::deleted(function ($navigation) {
            Cache::tags(['navigation', "locale_{$navigation->lang}"])->flush();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Get parent navigation
    public function parent()
    {
        if ($this->parent_id == 0) {
            return null;
        } else {
            return $this->belongsTo(Navigation::class, 'parent_id');
        }
    }
}
