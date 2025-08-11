<?php

namespace App\Models;

use App\Models\Pivots\SharepointFileable;
use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $sharepoint_id
 * @property string $id
 * @property-read Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SharepointFileable> $fileables
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Institution> $institutions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types
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

    public function fileables(): \Illuminate\Database\Eloquent\Relations\HasMany
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
