<?php

namespace App\Models;

use App\Services\Typesense\SyncContentSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tag_id
 * @property string $taggable_type
 * @property int $taggable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Tag|null $tag
 * @property-read Model|\Eloquent $taggable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable query()
 *
 * @mixin \Eloquent
 */
class Taggable extends MorphPivot
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn (self $pivot) => SyncContentSearch::taggableAfterCommit($pivot->taggable_type, $pivot->taggable_id));
        static::deleted(fn (self $pivot) => SyncContentSearch::taggableAfterCommit($pivot->taggable_type, $pivot->taggable_id));
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function taggable()
    {
        return $this->morphTo();
    }
}
