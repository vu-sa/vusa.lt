<?php

namespace App\Models;

use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

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
            'language' => $this->language ?? 'Unknown',
            'content_type' => $this->content_type,
            'institution_name_lt' => $this->institution ? $this->institution->getTranslation('name', 'lt') : null,
            'institution_name_en' => $this->institution ? $this->institution->getTranslation('name', 'en') : null,
            'tenant_shortname' => $this->institution && $this->institution->tenant ? $this->institution->tenant->shortname : null,
            'tenant_name' => $this->institution && $this->institution->tenant ? $this->institution->tenant->name : null,
            'tenant_type' => $this->institution && $this->institution->tenant ? $this->institution->tenant->type : null,
            'is_in_effect' => $this->calculateIsInEffect(),
            'anonymous_url' => $this->anonymous_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
            // Enhanced faceting fields
            'content_type_category' => $this->getContentTypeCategory(),
            'language_code' => $this->getLanguageCode(),
            'tenant_hierarchy' => $this->getTenantHierarchy(),
            'date_range_bucket' => $this->getDateRangeBucket(),
            'file_extension' => $this->getFileExtension(),
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

        // Add parent tenant if exists
        if ($tenant->parent_id) {
            $parent = $tenant->parent;
            if ($parent) {
                array_unshift($hierarchy, $parent->shortname);
            }
        }

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
     * Get file extension from document name
     */
    protected function getFileExtension(): string
    {
        if (! $this->name) {
            return 'unknown';
        }

        $extension = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'pdf',
            'doc', 'docx' => 'word',
            'xls', 'xlsx' => 'excel',
            'ppt', 'pptx' => 'powerpoint',
            'txt' => 'text',
            'url' => 'link',
            default => 'other'
        };
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // Only index documents that have anonymous access (public)
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
        // For metadata fields
        $contentField = 'Turinys'; // Content Type
        $institutionField = 'Padalinys'; // Entity

        $graph = new SharepointGraphService(siteId: $this->sharepoint_site_id, driveId: config('filesystems.sharepoint.archive_drive_id'), listId: $this->sharepoint_list_id);

        $additionalData = $graph->getListItem($this->sharepoint_site_id, $this->sharepoint_list_id, $this->sharepoint_id)->getAdditionalData();

        if ($this->eTag === $additionalData['@odata.etag']) {

            $this->checked_at = Carbon::now();
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

        $this->save();

        $this->refresh();

        return $this;
    }
}
