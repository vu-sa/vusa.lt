<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Contracts\SharepointFileableContract;
use App\Enums\InstitutionScope;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\InstitutionInstitutionType;
use App\Models\Pivots\Relationshipable;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasTypeHierarchy;
use App\Models\Traits\LogsModelActivity;
use App\Services\InstitutionScopeResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property string|null $slug
 * @property array<array-key, mixed>|null $extra_attributes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Collection<int, InstitutionType> $descendants
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read string|null $force_delete_blocked_reason
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read array $translatable_columns_from
 * @property-read InstitutionInstitutionType|Relationshipable|null $pivot
 * @property-read Collection<int, Relationship> $incomingRelationships
 * @property-read Collection<int, Institution> $institutions
 * @property-read Collection<int, Relationship> $outgoingRelationships
 * @property-read InstitutionType|null $parent
 * @property-read Collection<int, InstitutionType> $recursiveDescendants
 * @property-read InstitutionType|null $recursiveParent
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\InstitutionTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionType withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['title', 'description', 'slug', 'parent_id', 'extra_attributes'])]
class InstitutionType extends Model implements GuardsForceDelete, SharepointFileableContract
{
    use GuardsForceDeleteWhenReferenced, HasContentRelationships, HasFactory, HasSharepointFiles, HasTranslations, HasTypeHierarchy, LogsModelActivity, SoftDeletes;

    protected $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return ['extra_attributes' => 'array'];
    }

    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $type): void {
            if ($type->isDirty('title')) {
                FileableNameUpdated::dispatch($type);
            }
        });

        $flush = function (): void {
            app(InstitutionScopeResolver::class)->flush();
            Cache::forget('all-institution-types-for-inertia');
        };

        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
        static::forceDeleted($flush);
    }

    /** @return BelongsToMany<Institution, $this, InstitutionInstitutionType> */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)->using(InstitutionInstitutionType::class);
    }

    public function ownGovernanceScope(): ?InstitutionScope
    {
        $value = $this->extra_attributes['governance_scope'] ?? null;

        return is_string($value) ? InstitutionScope::tryFrom($value) : null;
    }

    public function governanceScope(): ?InstitutionScope
    {
        return app(InstitutionScopeResolver::class)->forType($this->id);
    }

    public function hasSiblingRelationshipsEnabled(): bool
    {
        return (bool) ($this->extra_attributes['enable_sibling_relationships'] ?? false);
    }

    public function hasCrossTenantSiblingRelationshipsEnabled(): bool
    {
        return (bool) ($this->extra_attributes['enable_cross_tenant_sibling_relationships'] ?? false);
    }

    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'trash.blockers.type_assignments' => $this->institutions()->withTrashed()->count(),
            'entities.institutionType.model' => $this->descendants()->withTrashed()->count(),
        ]);
    }
}
