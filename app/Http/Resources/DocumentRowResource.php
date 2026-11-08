<?php

namespace App\Http\Resources;

use App\Enums\DocumentStatus;
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
    /** @return array{id: int, title: string, name: string, status: DocumentStatus, content_type: string|null, language: string|null, document_date: string|null, institution: array{id: string, name: string, tenant_shortname: string|null}|null, sharepoint_institution_label: string|null, sharepoint_path: string|null, sharepoint_web_url: string|null, sharepoint_modified_at: string|null, removed_from_sharepoint_at: string|null, problems: list<'institution'|'unknown_institution'|'content_type'|'document_date'|'language'>, sync_status: 'pending'|'syncing'|'success'|'failed'|'imported', sync_error_message: string|null, public_url: string|null, can: array{update: bool}} */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'name' => $this->name,
            'status' => $this->status,
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
