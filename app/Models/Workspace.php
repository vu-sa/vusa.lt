<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\WorkspaceMemberRole;
use App\Models\Traits\HasComments;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A user-created meeting-preparation space with collaborative documents,
 * discussions, and links to the records being prepared for. Attaching an
 * institution grants its active representatives automatic access.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string|null $institution_id
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Institution|null $institution
 * @property-read User|null $creator
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, WorkspaceDocument> $documents
 * @property-read Collection<int, WorkspaceLink> $links
 * @property-read Collection<int, Comment> $comments
 *
 * @method static \Database\Factories\WorkspaceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Workspace query()
 *
 * @mixin \Eloquent
 */
class Workspace extends Model implements Commentable
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasComments, HasFactory, HasUlids, SoftDeletes;

    protected $guarded = [];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkspaceDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(WorkspaceDocument::class);
    }

    /**
     * @return HasMany<WorkspaceLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(WorkspaceLink::class);
    }

    public function isMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }

    public function isAdmin(User $user): bool
    {
        return $this->members()
            ->whereKey($user->id)
            ->wherePivot('role', WorkspaceMemberRole::Admin->value)
            ->exists();
    }

    /**
     * Whether the user currently holds a duty in the attached institution
     * (institution attachment grants its active representatives access).
     */
    public function hasInstitutionAccess(User $user): bool
    {
        if ($this->institution_id === null) {
            return false;
        }

        return $user->current_duties()
            ->where('duties.institution_id', $this->institution_id)
            ->exists();
    }
}
