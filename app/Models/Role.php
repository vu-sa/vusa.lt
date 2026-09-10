<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Type> $attachable_types
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, Type> $types
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, User> $currentUsersThroughDuties
 * @property-read Collection<int, User> $usersThroughDuties
 *
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 *
 * @mixin \Eloquent
 */
class Role extends SpatieRole
{
    use HasFactory, HasRelationships, HasUlids;

    public function duties(): MorphToMany
    {
        return $this->morphedByMany(Duty::class, 'model', 'model_has_roles');
    }

    public function usersThroughDuties(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->users());
    }

    public function currentUsersThroughDuties(): HasManyDeep
    {
        return $this->usersThroughDuties()
            ->where(function ($query): void {
                $query->whereNull('dutiables.end_date')
                    ->orWhere('dutiables.end_date', '>=', now());
            });
    }

    public function attachable_types()
    {
        return $this->belongsToMany(Type::class, 'role_can_attach_types');
    }

    public function types()
    {
        return $this->belongsToMany(Type::class);
    }
}
