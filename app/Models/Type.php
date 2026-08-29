<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Contracts\SharepointFileableContract;
use App\Enums\InstitutionScope;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\Relationshipable;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use App\Services\InstitutionScopeResolver;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property string|null $model_type
 * @property string|null $slug
 * @property array<array-key, mixed>|null $extra_attributes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Collection<int, Type> $descendants
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read string|null $force_delete_blocked_reason
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read array $translatable_columns_from
 * @property-read RoleType|Relationshipable|null $pivot
 * @property-read Collection<int, Relationship> $incomingRelationships
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, Relationship> $outgoingRelationships
 * @property-read Type|null $parent
 * @property-read Type|null $recursiveParent
 * @property-read Collection<int, Role> $roles
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\TypeFactory factory($count = null, $state = [])
 * @method static Builder<static>|Type forDuties()
 * @method static Builder<static>|Type forInstitutions()
 * @method static Builder<static>|Type newModelQuery()
 * @method static Builder<static>|Type newQuery()
 * @method static Builder<static>|Type onlyTrashed()
 * @method static Builder<static>|Type query()
 * @method static Builder<static>|Type whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Type whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Type whereLocale(string $column, string $locale)
 * @method static Builder<static>|Type whereLocales(string $column, array $locales)
 * @method static Builder<static>|Type withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Type withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Type extends Model implements GuardsForceDelete, SharepointFileableContract
{
    use GuardsForceDeleteWhenReferenced, HasContentRelationships, HasFactory, HasSharepointFiles, HasTranslations, LogsModelActivity, SoftDeletes;

    #[\Override]
    protected $guarded = [];

    protected $translatable = ['title', 'description'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'extra_attributes' => 'array',
        ];
    }

    #[\Override]
    protected static function booted()
    {
        static::saving(function (Type $type): void {
            // Dispatch event when title is about to change - SharePoint must succeed first
            if ($type->isDirty('title')) {
                FileableNameUpdated::dispatch($type);
            }
        });

        // The scope map is keyed on the whole type tree, so any structural change invalidates it.
        $flushScopes = fn () => app(InstitutionScopeResolver::class)->flush();

        static::saved($flushScopes);
        static::deleted($flushScopes);
        static::restored($flushScopes);
        static::forceDeleted($flushScopes);
    }

    /**
     * The only models a type may be attached to, mapped to the relation holding them.
     *
     * A request-supplied `model_type` is resolved through this map — it is never turned into
     * a method name and dispatched, which previously allowed `roles` to be synced and any
     * unknown value to raise a 500. Keyed by morph alias, which is what the column stores and
     * what Store/UpdateTypeRequest validates against.
     *
     * @var array<string, string>
     */
    public const TYPEABLE_RELATIONS = [
        'institution' => 'institutions',
        'duty' => 'duties',
    ];

    /**
     * Resolve the relation that holds the models of this type's `model_type`.
     */
    public function typeableRelation(): ?string
    {
        return self::TYPEABLE_RELATIONS[$this->model_type] ?? null;
    }

    /**
     * @return MorphToMany<Institution, $this>
     */
    public function institutions(): MorphToMany
    {
        return $this->morphedByMany(Institution::class, 'typeable');
    }

    public function duties()
    {
        return $this->morphedByMany(Duty::class, 'typeable');
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

    public function recursiveParent(): BelongsTo
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
        // The class is resolved through the morph map rather than from the stored string, so
        // model_type can never name a class this method was not written for.
        return match ($this->model_type) {
            MorphMap::alias(Institution::class) => Institution::query()
                ->select('id', 'name', 'tenant_id')->with('tenants')->orderBy('name')->get(),
            MorphMap::alias(Duty::class) => Duty::query()
                ->select('id', 'name', 'institution_id')->with('tenants')->orderBy('name')->get(),
            default => collect(),
        };
    }

    /**
     * Scope to filter types for Institutions only.
     */
    /**
     * @param  Builder<Type>  $query
     * @return Builder<Type>
     */
    public function scopeForInstitutions(Builder $query): Builder
    {
        return $query->where('model_type', MorphMap::alias(Institution::class));
    }

    /**
     * Scope to filter types for Duties only.
     */
    public function scopeForDuties($query)
    {
        return $query->where('model_type', MorphMap::alias(Duty::class));
    }

    /**
     * The scope this type declares itself, ignoring the parent tree.
     */
    public function ownGovernanceScope(): ?InstitutionScope
    {
        $value = $this->extra_attributes['governance_scope'] ?? null;

        return is_string($value) ? InstitutionScope::tryFrom($value) : null;
    }

    /**
     * The scope in force for this type, inherited from the nearest ancestor that declares one.
     */
    public function governanceScope(): ?InstitutionScope
    {
        return app(InstitutionScopeResolver::class)->forType($this->id);
    }

    /**
     * Check if sibling relationships are enabled for this type.
     * When enabled, institutions with this type in the same tenant
     * will automatically be related as siblings.
     */
    public function hasSiblingRelationshipsEnabled(): bool
    {
        return (bool) ($this->extra_attributes['enable_sibling_relationships'] ?? false);
    }

    /**
     * Check if cross-tenant sibling relationships are enabled for this type.
     * When enabled, institutions of this type can see institutions of the same type
     * across tenant boundaries (pagrindinis <-> padalinys).
     *
     * Authorization is one-directional:
     * - Pagrindinis sees padalinys siblings with authorized: true (full data access)
     * - Padalinys sees pagrindinis sibling with authorized: false (visible but no agenda items)
     */
    public function hasCrossTenantSiblingRelationshipsEnabled(): bool
    {
        return (bool) ($this->extra_attributes['enable_cross_tenant_sibling_relationships'] ?? false);
    }

    /**
     * `typeables`, `role_type` and `role_can_attach_types` all cascade, so permanently
     * deleting a type would silently strip it from every institution and duty that
     * carries it, and `types.parent_id` is SET NULL, which would orphan child types.
     */
    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'trash.blockers.type_assignments' => DB::table('typeables')->where('type_id', $this->id)->count(),
            'entities.type.model' => static::query()->where('parent_id', $this->id)->count(),
        ]);
    }
}
