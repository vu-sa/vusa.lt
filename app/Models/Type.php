<?php

namespace App\Models;

use App\Contracts\SharepointFileableContract;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property string|null $model_type
 * @property string|null $slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read Collection<int, Type> $descendants
 * @property-read Collection<int, \App\Models\Duty> $duties
 * @property-read Collection<int, \App\Models\SharepointFile> $files
 * @property-read \App\Models\RoleType|\App\Models\Pivots\Relationshipable|null $pivot
 * @property-read Collection<int, \App\Models\Relationship> $incomingRelationships
 * @property-read Collection<int, \App\Models\Institution> $institutions
 * @property-read Collection<int, \App\Models\Meeting> $meetings
 * @property-read Collection<int, \App\Models\Relationship> $outgoingRelationships
 * @property-read Type|null $parent
 * @property-read Type|null $recursiveParent
 * @property-read Collection<int, \App\Models\Role> $roles
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\TypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Type withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Type extends Model implements SharepointFileableContract
{
    use HasContentRelationships, HasFactory, HasSharepointFiles, HasTranslations, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $translatable = ['title', 'description'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function institutions()
    {
        return $this->morphedByMany(Institution::class, 'typeable');
    }

    public function duties()
    {
        return $this->morphedByMany(Duty::class, 'typeable');
    }

    public function meetings()
    {
        return $this->morphedByMany(Meeting::class, 'typeable');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->using(RoleType::class);
    }

    public function descendants()
    {
        return $this->hasMany(Type::class, 'parent_id');
    }

    public function recursiveDescendants()
    {
        return $this->descendants()->with('recursiveDescendants');
    }

    public function parent()
    {
        return $this->belongsTo(Type::class, 'parent_id');
    }

    public function recursiveParent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->parent()->with('recursiveParent');
    }

    public function pushAndRecursiveDescendants($type, $flattened = null): Collection
    {
        if (is_null($flattened)) {
            $flattened = new Collection;
        }

        foreach ($type->recursiveDescendants as $descendant) {
            $this->pushAndRecursiveDescendants($descendant, $flattened);
        }
        // remove recursivedescendants
        $flattened->push($type);
        $flattened->forget('recursiveDescendants');

        return $flattened;
    }

    public function getDescendantsAndSelf(): Collection
    {
        // Because the descendants were pushed at the end, we need to reverse it
        return $this->pushAndRecursiveDescendants($this)->unique('id')->reverse()->values();
    }

    public function pushAndRecursiveParents($type, $flattened = null): Collection
    {
        if (is_null($flattened)) {
            $flattened = new Collection;
        }

        if ($parent = $type->recursiveParent) {
            $this->pushAndRecursiveParents($parent, $flattened);
        }

        // remove recursiveparents
        $flattened->push($type);

        return $flattened;
    }

    public function getParentsAndSelf(): Collection
    {
        // Because the parents were pushed at the end, we need to reverse it
        return $this->pushAndRecursiveParents($this)->unique('id')->reverse()->values();
    }

    public function allModelsFromModelType()
    {
        if (Str::contains($this->model_type, 'Institution')) {
            return $this->model_type::select('id', 'name', 'tenant_id')->with('tenants')->orderBy('name')->get();
        } elseif (Str::contains($this->model_type, 'Duty')) {
            return $this->model_type::select('id', 'name', 'institution_id')->with('tenants')->orderBy('name')->get();
        } elseif (Str::contains($this->model_type, 'Meeting')) {
            return $this->model_type::select('id', 'title')->with('tenants')->orderBy('title')->get();
        }
    }
}
