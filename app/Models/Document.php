<?php

namespace App\Models;

use App\Helpers\ShortUrlHelper;
use App\Services\DocumentSharepointSyncService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property string $name
 * @property string $title
 * @property string $sharepoint_id
 * @property string|null $eTag
 * @property Carbon|null $document_date
 * @property string|null $institution_id
 * @property string|null $content_type
 * @property string|null $language
 * @property string|null $summary
 * @property string|null $anonymous_url
 * @property string|null $sharepoint_permission_id
 * @property bool $is_active
 * @property string $sharepoint_site_id
 * @property string $sharepoint_list_id
 * @property Carbon $created_at
 * @property Carbon|null $checked_at
 * @property string $sync_status Status of SharePoint sync: pending, syncing, success, failed
 * @property string|null $sync_error_message Error message from failed sync attempts
 * @property int $sync_attempts Number of sync attempts made
 * @property Carbon|null $last_sync_attempt_at Timestamp of last sync attempt
 * @property Carbon $updated_at
 * @property Carbon|null $effective_date
 * @property Carbon|null $expiration_date
 * @property-read bool|null $is_in_effect
 * @property-read Institution|null $institution
 * @property-read Collection<int, Tenant> $tenant
 *
 * @method static \Database\Factories\DocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 *
 * @mixin \Eloquent
 */
class Document extends Model
{
    use HasFactory, HasRelationships, Searchable;

    protected $guarded = [];

    protected $hidden = ['sharepoint_id', 'eTag', 'public_url_created_at', 'sharepoint_site_id', 'sharepoint_list_id', 'sharepoint_permission_id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'document_date' => 'datetime',
            'effective_date' => 'datetime',
            'expiration_date' => 'datetime',
            'checked_at' => 'datetime',
            'last_sync_attempt_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::saved(function ($document) {
            Cache::tags(['documents'])->flush();
        });

        static::deleted(function ($document) {
            Cache::tags(['documents'])->flush();
        });
    }

    public function toSearchableArray(): array
    {
        // Load the tenant relationship if not already loaded
        if (! $this->relationLoaded('institution') || ($this->institution && ! $this->institution->relationLoaded('tenant'))) {
            $this->load('institution.tenant');
        }

        $searchableArray = [
            'id' => (string) $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'name' => $this->name,
            'language' => $this->language,
            'content_type' => $this->content_type,
            'institution_id' => $this->institution ? $this->institution->id : null,
            'institution_name_lt' => $this->institution ? $this->institution->getTranslation('name', 'lt') : null,
            'institution_name_en' => $this->institution ? $this->institution->getTranslation('name', 'en') : null,
            'tenant_shortname' => $this->institution && $this->institution->tenant ? $this->institution->tenant->shortname : null,
            'anonymous_url' => $this->anonymous_url,
            'share_url' => $this->anonymous_url ? ShortUrlHelper::documentUrl($this->id) : null,
            'is_active' => $this->is_active,
            'sync_status' => $this->sync_status,
            'checked_at' => $this->checked_at ? $this->checked_at->timestamp : null,
            'is_in_effect' => $this->calculateIsInEffect(),
            'created_at' => $this->created_at->timestamp,
            // Enhanced faceting fields
            'content_type_category' => $this->getContentTypeCategory(),
            'language_code' => $this->getLanguageCode(),
            'tenant_hierarchy' => $this->getTenantHierarchy(),
            'date_range_bucket' => $this->getDateRangeBucket(),
        ];

        // Only include document_date if it exists (used for sorting and faceting)
        if ($this->document_date) {
            $searchableArray['document_date'] = strtotime($this->document_date);
            // Add searchable date fields for year-based queries (e.g., "2015", "2024")
            $searchableArray['document_year'] = (string) $this->document_date->year;
            $searchableArray['document_date_formatted'] = $this->document_date->format('Y-m-d');
        }

        return $searchableArray;
    }

    /**
     * Get content type category for better faceting
     */
    protected function getContentTypeCategory(): string
    {
        if (! $this->content_type) {
            return 'Kita';
        }

        if (str_starts_with($this->content_type, 'VU SA P ')) {
            return 'VU SA P';
        }

        if (str_starts_with($this->content_type, 'VU SA ')) {
            return 'VU SA';
        }

        return 'Kita';
    }

    /**
     * Get standardized language code
     */
    protected function getLanguageCode(): string
    {
        return match ($this->language) {
            'Lietuvių', 'Lithuanian' => 'lt',
            'Anglų', 'English' => 'en',
            default => 'unknown'
        };
    }

    /**
     * Get tenant hierarchy path for nested filtering
     */
    protected function getTenantHierarchy(): array
    {
        if (! $this->institution || ! $this->institution->tenant) {
            return [];
        }

        $tenant = $this->institution->tenant;
        $hierarchy = [$tenant->shortname];

        return $hierarchy;
    }

    /**
     * Get date range bucket for faceted date filtering
     */
    protected function getDateRangeBucket(): string
    {
        if (! $this->document_date) {
            return 'unknown';
        }

        $now = now();
        $docDate = $this->document_date;

        $monthsAgo = $now->diffInMonths($docDate);

        if ($monthsAgo <= 1) {
            return 'recent_1month';
        }
        if ($monthsAgo <= 3) {
            return 'recent_3months';
        }
        if ($monthsAgo <= 6) {
            return 'recent_6months';
        }
        if ($monthsAgo <= 12) {
            return 'recent_1year';
        }
        if ($monthsAgo <= 24) {
            return 'recent_2years';
        }

        return 'older';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // For admin context, index all documents regardless of publication status
        if (Context::get('search_context') === 'admin') {
            return true;
        }

        // For public context, only index documents that have anonymous access
        return ! empty($this->anonymous_url);
    }

    /**
     * Get the engine used to index the model.
     * Documents should use Typesense for public search.
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function tenant()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    /**
     * Calculate if document is currently in effect based on effective and expiration dates
     */
    public function calculateIsInEffect(): ?bool
    {
        $now = Carbon::now();

        // If both dates are null, document has no defined validity period
        if ($this->effective_date === null && $this->expiration_date === null) {
            return null; // UI will not show Galioja/Negalioja for these
        }

        // Check effective date constraint (if exists)
        $effectiveCheck = $this->effective_date === null || $now->greaterThanOrEqualTo($this->effective_date);

        // Check expiration date constraint (if exists)
        $expirationCheck = $this->expiration_date === null || $now->lessThanOrEqualTo($this->expiration_date);

        // Document is in effect if both constraints are satisfied
        return $effectiveCheck && $expirationCheck;
    }

    // Accessor attribute that uses the method above
    protected function getIsInEffectAttribute(): ?bool
    {
        return $this->calculateIsInEffect();
    }

    // Also used in SharepointGraphService::batchProcessDocuments
    public function refreshFromSharepoint(bool $force = false): ?self
    {
        return app(DocumentSharepointSyncService::class)->sync($this, $force);
    }
}
