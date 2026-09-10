<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $urlable_type
 * @property int $urlable_id
 * @property string $locale
 * @property string $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model $urlable
 *
 * @method static \Database\Factories\PublicUrlFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicUrl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicUrl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicUrl query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['urlable_type', 'urlable_id', 'locale', 'url'])]
class PublicUrl extends Model
{
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function urlable(): MorphTo
    {
        return $this->morphTo();
    }
}
