<?php

namespace App\Models;

use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Document extends Model
{
    use HasFactory, HasRelationships, Searchable;

    protected $guarded = [];

    protected $hidden = ['sharepoint_id', 'eTag', 'public_url_created_at', 'sharepoint_site_id', 'sharepoint_list_id', 'created_at', 'updated_at'];

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
    }

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function tenant()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    public function refreshFromSharepoint()
    {
        $contentField = 'Turinys'; // Content Type
        $institutionField = 'Padalinys'; // Entity

        $graph = new SharepointGraphService(siteId: $this->sharepoint_site_id, driveId: null, listId: $this->sharepoint_list_id);

        $additionalData = $graph->getListItem($this->sharepoint_site_id, $this->sharepoint_list_id, $this->sharepoint_id)->getAdditionalData();

        if ($this->eTag === $additionalData['@odata.etag']) {

            $this->checked_at = Carbon::now();
            $this->save();

            return null;
        }

        $this->document_date = isset($additionalData['Date']) ? Carbon::parseFromLocale(time: $additionalData['Date'], timezone: 'UTC')->setTimezone('Europe/Vilnius') : $this->document_date;

        $this->name = $additionalData['Name'] ?? $this->name;
        $this->title = $additionalData['Title'] ?? $this->title;
        $this->eTag = $additionalData['@odata.etag'] ?? $this->eTag;
        $this->language = $additionalData['Language'] ?? $this->language;

        if (isset($additionalData[$institutionField]['Label'])) {
            $this->institution()->associate(Institution::query()->where('name', $additionalData[$institutionField]['Label'])->orWhere('short_name', $additionalData[$institutionField]['Label'])->first());
        }

        $this->content_type = isset($additionalData[$contentField]['Label']) ? $additionalData[$contentField]['Label'] : $this->content_type;

        $this->summary = $additionalData['Summary'] ?? $this->summary;

        // Get drive item
        $driveItem = $graph->getDriveItemByListItem($this->sharepoint_site_id, $this->sharepoint_list_id, $this->sharepoint_id);

        // Add thumbnails
        /*collect($driveItem->getThumbnails())->each(function ($thumbnailSet) {*/
        /*    $this->thumbnail_url = $thumbnailSet->getLarge()->getUrl();*/
        /*});*/

        $anonymous_permission = $graph->getDriveItemPublicLink($driveItem->getId());

        if ($anonymous_permission === null) {
            $anonymous_permission = $graph->createPublicPermission(siteId: $this->sharepoint_site_id, driveItemId: $driveItem->getId(), datetime: false);

            $this->anonymous_url = $anonymous_permission->getLink()->getWebUrl();

            /*$this->anonymous_url_expiration_date = Carbon::parse($anonymous_permission->getExpirationDateTime());*/
        } else {
            $this->anonymous_url = $anonymous_permission->getLink()->getWebUrl();
            /*$this->anonymous_url_expiration_date = Carbon::parse($anonymous_permission->getExpirationDateTime());*/
        }

        $this->checked_at = Carbon::now();

        $this->save();

        $this->refresh();

        return $this;
    }
}
