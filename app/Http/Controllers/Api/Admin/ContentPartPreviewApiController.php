<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\PreviewContentPartsRequest;
use App\Models\Tenant;
use App\Services\ContentResolution\ContentPartResolver;
use App\Services\ContentResolution\ResolutionContext;
use Illuminate\Http\JsonResponse;

/**
 * Batch-resolves unsaved rich-content blocks for the admin editor's live preview
 * (ContentEditorFactory's per-block preview, RichContentEditor's "preview all"). Runs
 * every block through `ContentPartResolver::resolveOne()` — the identical code path
 * public rendering uses — so preview and production can never diverge into two
 * implementations. Batched by design: the editor's "preview all" toggle would
 * otherwise fire one request per resolvable block every time it's switched on.
 */
class ContentPartPreviewApiController extends ApiController
{
    public function __invoke(PreviewContentPartsRequest $request, ContentPartResolver $resolver): JsonResponse
    {
        $tenant = Tenant::findOrFail($request->validated('tenant_id'));
        $context = new ResolutionContext(
            tenant: $tenant,
            locale: $request->validated('locale') ?? app()->getLocale(),
            subdomain: $tenant->subdomain(),
            isPreview: true,
        );

        $resolved = [];
        foreach ($request->validated('parts') as $part) {
            $resolved[$part['key']] = $resolver->resolveOne(
                $part['type'],
                (array) $part['json_content'],
                $part['options'] ?? null,
                $context,
            );
        }

        return $this->jsonSuccess(['resolved' => (object) $resolved]);
    }
}
