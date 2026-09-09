<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\PermalinkPreviewRequest;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Previews the permalink (and full URL) a News/Page create form would end up with for the
 * title as currently typed — including the `-2` collision suffix, if any — without writing
 * anything. The record doesn't exist yet, so the URL is built from a throwaway, unsaved
 * instance calling its own `publicUrl()`, reusing the exact logic a real save would use.
 */
class PermalinkPreviewApiController extends ApiController
{
    public function news(PermalinkPreviewRequest $request, ModelAuthorizer $authorizer): JsonResponse
    {
        $actor = $this->requireAuth($request);

        if (! $actor->can('create', News::class)) {
            return $this->jsonForbidden();
        }

        $tenantId = $this->targetTenantId($actor, $authorizer, 'news.create.padalinys');

        if ($tenantId === null) {
            throw ValidationException::withMessages(['title' => __('messages.news.no_available_tenant')]);
        }

        $permalink = GenerateUniqueSlug::execute(News::class, $request->string('title')->toString(), $tenantId);

        $preview = (new News)->setRawAttributes([
            'permalink' => $permalink,
            'tenant_id' => $tenantId,
            'lang' => $request->string('lang')->toString(),
        ], true);

        return $this->jsonSuccess(['permalink' => $permalink, 'url' => $preview->publicUrl()]);
    }

    public function page(PermalinkPreviewRequest $request, ModelAuthorizer $authorizer): JsonResponse
    {
        $actor = $this->requireAuth($request);

        if (! $actor->can('create', Page::class)) {
            return $this->jsonForbidden();
        }

        $tenantId = $this->targetTenantId($actor, $authorizer, 'pages.create.padalinys');

        if ($tenantId === null) {
            throw ValidationException::withMessages(['title' => __('messages.pages.no_available_tenant')]);
        }

        $permalink = GenerateUniqueSlug::execute(Page::class, $request->string('title')->toString(), $tenantId);

        $preview = (new Page)->setRawAttributes([
            'permalink' => $permalink,
            'tenant_id' => $tenantId,
            'lang' => $request->string('lang')->toString(),
        ], true);

        return $this->jsonSuccess(['permalink' => $permalink, 'url' => $preview->publicUrl()]);
    }

    /**
     * Mirrors the tenant resolution in NewsController::store()/PageController::store() — the
     * preview must reflect the tenant the record would actually be created for.
     */
    private function targetTenantId(User $actor, ModelAuthorizer $authorizer, string $permission): ?int
    {
        if ($actor->isSuperAdmin()) {
            return Tenant::main()?->id;
        }

        return $authorizer->duties($actor, $permission)->first()?->tenants->first()?->id;
    }
}
