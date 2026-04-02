<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ContentPart> $parts
 *
 * @method static \Database\Factories\ContentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content query()
 *
 * @mixin \Eloquent
 */
class Content extends Model
{
    use HasFactory;

    protected $with = ['parts'];

    protected $guarded = [];

    public function parts(): HasMany
    {
        return $this->hasMany(ContentPart::class)->orderBy('order');
    }
}
