<?php

namespace App\Models;

use App\Support\WorkspaceLinkables;
use Database\Factories\WorkspaceLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A polymorphic reference from a workspace to a record being prepared for.
 * Allowed morph types are constrained by {@see WorkspaceLinkables}.
 *
 * @property int $id
 * @property string $workspace_id
 * @property string $linkable_type
 * @property string $linkable_id
 * @property string|null $added_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Model $linkable
 * @property-read User|null $addedBy
 *
 * @method static \Database\Factories\WorkspaceLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceLink query()
 *
 * @mixin \Eloquent
 */
class WorkspaceLink extends Model
{
    /** @use HasFactory<WorkspaceLinkFactory> */
    use HasFactory;

    protected $guarded = [];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
