<?php

namespace App\Models;

use App\Models\Pivots\SharepointFileable;
use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $sharepoint_id
 * @property string $id
 * @property-read Model|\Eloquent $commentable
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, SharepointFileable> $fileables
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, Meeting> $meetings
 * @property-read Collection<int, Type> $types
 *
 * @method static \Database\Factories\SharepointFileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharepointFile query()
 *
 * @mixin \Eloquent
 */
class SharepointFile extends Model
{
    use HasComments, HasFactory, HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    public function fileables(): HasMany
    {
        return $this->hasMany(SharepointFileable::class, 'sharepoint_file_id', 'id');
    }

    public function types()
    {
        return $this->morphedByMany(Type::class, 'fileable');
    }

    public function institutions()
    {
        return $this->morphedByMany(Institution::class, 'fileable');
    }

    public function meetings()
    {
        return $this->morphedByMany(Meeting::class, 'fileable');
    }
}
