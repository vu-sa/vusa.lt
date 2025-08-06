<?php

namespace App\Models;

use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
 * @property bool $is_active
 * @property string $sharepoint_site_id
 * @property string $sharepoint_list_id
 * @property Carbon $created_at
 * @property Carbon|null $checked_at
 * @property Carbon $updated_at
 * @property Carbon|null $effective_date
 * @property Carbon|null $expiration_date
 * @property-read bool|null $is_in_effect
 * @property-read \App\Models\Institution|null $institution
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenant
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

    protected $hidden = ['sharepoint_id', 'eTag', 'public_url_created_at', 'sharepoint_site_id', 'sharepoint_list_id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'document_date' => 'datetime',
        'effective_date' => 'datetime',
        'expiration_date' => 'datetime',
        'checked_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($document) {
            Cache::tags(['documents'])->flush();
        });

        static::deleted(function ($document) {
            Cache::tags(['documents'])->flush();
        });
    }

    public function toSearchableArray()
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
        if (\Illuminate\Support\Facades\Context::get('search_context') === 'admin') {
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
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
    }

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
    public function refreshFromSharepoint()
    {
        // Update sync tracking
        $this->sync_status = 'syncing';
        $this->sync_attempts = ($this->sync_attempts ?? 0) + 1;
        $this->last_sync_attempt_at = Carbon::now();
        $this->sync_error_message = null;
        $this->save();

        try {
            // TODO: Move to configuration - hardcoded SharePoint field names
            $contentField = 'Turinys'; // Content Type - TODO: Make configurable
            $institutionField = 'Padalinys'; // Entity - TODO: Make configurable

            $graph = new SharepointGraphService(siteId: $this->sharepoint_site_id, driveId: config('filesystems.sharepoint.archive_drive_id'), listId: $this->sharepoint_list_id);

            $additionalData = $graph->getListItem($this->sharepoint_site_id, $this->sharepoint_list_id, $this->sharepoint_id)->getAdditionalData();

            if ($this->eTag === $additionalData['@odata.etag']) {
                $this->checked_at = Carbon::now();
                $this->sync_status = 'success';
                $this->save();

                return null;
            }

            $this->document_date = isset($additionalData['Date']) ? Carbon::parseFromLocale(time: $additionalData['Date'], timezone: 'UTC')->setTimezone('Europe/Vilnius') : $this->document_date;
            $this->effective_date = isset($additionalData['Effective_x0020_Date']) ? Carbon::parseFromLocale(time: $additionalData['Effective_x0020_Date'], timezone: 'UTC')->setTimezone('Europe/Vilnius') : $this->effective_date;
            $this->expiration_date = isset($additionalData['Expiration_x0020_Date0']) ? Carbon::parseFromLocale(time: $additionalData['Expiration_x0020_Date0'], timezone: 'UTC')->setTimezone('Europe/Vilnius') : $this->expiration_date;

            $this->name = $additionalData['Name'] ?? $this->name;
            $this->title = $additionalData['Title'] ?? $this->title;
            $this->eTag = $additionalData['@odata.etag'] ?? $this->eTag;
            $this->language = $additionalData['Language'] ?? $this->language;

            if (isset($additionalData[$institutionField]['Label'])) {
                $this->institution()->associate(Institution::query()->where('name->lt', $additionalData[$institutionField]['Label'])->orWhere('short_name->lt', $additionalData[$institutionField]['Label'])->first());
            }

            $this->content_type = isset($additionalData[$contentField]['Label']) ? $additionalData[$contentField]['Label'] : $this->content_type;

            $this->summary = $additionalData['Summary'] ?? $this->summary;

            // Get drive item
            $driveItem = $graph->getDriveItemByListItem($this->sharepoint_site_id, $this->sharepoint_list_id, $this->sharepoint_id);

            // Add thumbnails
            /* collect($driveItem->getThumbnails())->each(function ($thumbnailSet) { */
            /*    $this->thumbnail_url = $thumbnailSet->getLarge()->getUrl(); */
            /* }); */

            $anonymous_permission = $graph->getDriveItemPublicLink($driveItem->getId());

            if ($anonymous_permission === null) {
                $anonymous_permission = $graph->createPublicPermission(siteId: $this->sharepoint_site_id, driveItemId: $driveItem->getId(), datetime: false);

                $this->anonymous_url = $anonymous_permission->getLink()->getWebUrl();

                /* $this->anonymous_url_expiration_date = Carbon::parse($anonymous_permission->getExpirationDateTime()); */
            } else {
                $this->anonymous_url = $anonymous_permission->getLink()->getWebUrl();
                /* $this->anonymous_url_expiration_date = Carbon::parse($anonymous_permission->getExpirationDateTime()); */
            }

            $this->checked_at = Carbon::now();
            $this->sync_status = 'success';
            $this->save();

            $this->refresh();

            return $this;
        } catch (\Exception $e) {
            // Log the error with context
            \Log::error('SharePoint document sync failed', [
                'document_id' => $this->id,
                'sharepoint_id' => $this->sharepoint_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sync_attempt' => $this->sync_attempts,
            ]);

            // Update sync status with error
            $this->sync_status = 'failed';
            $this->sync_error_message = $e->getMessage();
            $this->save();

            // Re-throw the exception to let the caller handle it
            throw $e;
        }
    }
}
