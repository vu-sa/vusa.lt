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

        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'language' => $this->language ?? 'Unknown',
            'content_type' => $this->content_type,
            'institution_name_lt' => $this->institution ? $this->institution->getTranslation('name', 'lt') : null,
            'institution_name_en' => $this->institution ? $this->institution->getTranslation('name', 'en') : null,
            'tenant_shortname' => $this->institution && $this->institution->tenant ? $this->institution->tenant->shortname : null,
            'document_date' => $this->document_date ? strtotime($this->document_date) : now()->timestamp,
            'anonymous_url' => $this->anonymous_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->timestamp,
        ];
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

    // Check if after effective date or before expiration date. If one is missing, it is ignored.
    protected function getIsInEffectAttribute()
    {
        if ($this->effective_date === null && $this->expiration_date === null) {
            return null;
        }

        if ($this->effective_date !== null && $this->expiration_date === null) {
            return Carbon::now()->isAfter($this->effective_date);
        }

        if ($this->effective_date === null && $this->expiration_date !== null) {
            return Carbon::now()->isBefore($this->expiration_date);
        }

        return Carbon::now()->isAfter($this->effective_date) && Carbon::now()->isBefore($this->expiration_date);
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
