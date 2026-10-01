<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Contracts\SharepointFileableContract;
use App\Enums\InstitutionScope;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\InstitutionInstitutionType;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasTypeHierarchy;
use App\Models\Traits\LogsModelActivity;
use App\Services\InstitutionScopeResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

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
