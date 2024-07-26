<?php

namespace App\Models;

use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            'name' => $this->name,
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function tenant()
    {
        return $this->hasManyDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    public function refreshFromSharepoint()
    {
        $graph = new SharepointGraphService(siteId: $this->sharepoint_site_id, driveId: null, listId: $this->sharepoint_list_id);

        $additionalData = $graph->getListItem($this->sharepoint_site_id, $this->sharepoint_list_id, $this->sharepoint_id)->getAdditionalData();

        if ($this->eTag === $additionalData['@odata.etag']) {
            return null;
        }

        $this->title = $additionalData['title'] ?? $this->title;
        $this->eTag = $additionalData['@odata.etag'] ?? $this->eTag;

        if (isset($additionalData['Padalinys']['Label'])) {
            $this->institution()->associate(Institution::query()->where('name', $additionalData['Padalinys']['Label'])->orWhere('short_name', $additionalData['Padalinys']['Label'])->first());
        }

        $this->content_type = isset($additionalData['Turinys']['Label']) ? $additionalData['Turinys']['Label'] : $this->content_type;

        $this->save();

        $this->refresh();

        return $this;
    }
}
