<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $type_id
 * @property string $typeable_type
 * @property string $typeable_id
 * @property-read Type|null $type
 * @property-read Model|\Eloquent|null $typeable
 *
 * @method static \Database\Factories\TypeableFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Typeable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Typeable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Typeable query()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
class Typeable extends MorphPivot
{
    use HasFactory;

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function typeable(): MorphTo
    {
        return $this->morphTo();
    }
}
