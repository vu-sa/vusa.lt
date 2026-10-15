<?php

namespace App\Http\Resources;

use App\Helpers\ShortUrlHelper;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A SharePoint document as the managers' views list it: the folder view, the pending and hidden
 * queues, and a meeting's suggestions. Expects `institution.tenant` loaded.
 *
 * @mixin Document
 */
class DocumentRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'name' => $this->name,
            'status' => $this->status->value,
            'content_type' => $this->content_type,
            'language' => $this->language,
            'document_date' => $this->document_date?->toDateString(),
            'institution' => $this->institution ? [
                'id' => $this->institution->id,
                'name' => $this->institution->getTranslation('short_name', 'lt') ?: $this->institution->getTranslation('name', 'lt'),
                'tenant_shortname' => $this->institution->tenant?->shortname,
            ] : null,
            'sharepoint_institution_label' => $this->sharepoint_institution_label,
            'sharepoint_path' => $this->sharepoint_path,
            'sharepoint_web_url' => $this->sharepoint_web_url,
            'sharepoint_modified_at' => $this->sharepoint_modified_at?->toIso8601String(),
            'removed_from_sharepoint_at' => $this->removed_from_sharepoint_at?->toIso8601String(),
            'problems' => $this->metadataProblems(),
            'sync_status' => $this->sync_status,
            'sync_error_message' => $this->sync_error_message,
            'public_url' => $this->isPublished() && $this->anonymous_url ? ShortUrlHelper::documentUrl($this->id) : null,
            'can' => ['update' => (bool) $request->user()?->can('publish', $this->resource)],
        ];
    }
}
