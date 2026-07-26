<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\Api\ApiController;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use App\Services\UmamiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-scoped page-view statistics for the Svetainė dashboard.
 *
 * Reads from the self-hosted Umami instance, scoped to a single tenant by its public
 * hostname (each tenant site is its own subdomain, and Umami records the hostname on
 * every event).
 */
class AnalyticsApiController extends ApiController
{
    /** @var array<string, int> Supported periods, in days back from today. */
    private const PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '12m' => 365,
    ];

    public function __construct(
        protected UmamiClient $umami,
        protected ModelAuthorizer $authorizer,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'period' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::PERIODS))],
        ]);

        $this->authorize('viewAny', Page::class);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);

        /**
         * The `viewAny` check above only proves the user may see *some* site content — it
         * says nothing about *this* tenant. Without this second check any authenticated
         * user could read any tenant's analytics by changing tenant_id.
         */
        $manageableTenantIds = GetTenantsForUpserts::execute('pages.update.padalinys', $this->authorizer)
            ->pluck('id');

        if (! $manageableTenantIds->contains($tenant->id)) {
            return $this->jsonForbidden();
        }

        $period = $validated['period'] ?? '30d';

        $overview = $this->umami->overview(
            $tenant->publicHostname(),
            now()->subDays(self::PERIODS[$period])->startOfDay(),
            now()->endOfDay(),
        );

        /**
         * A null overview means Umami is unconfigured or unreachable. That is not an error
         * the coordinator can act on, so return an explicit "no data" payload and let the
         * dashboard render an empty state instead of a failed request.
         */
        if ($overview === null) {
            return $this->jsonSuccess([
                'available' => false,
                'period' => $period,
                'hostname' => $tenant->publicHostname(),
                'totals' => null,
                'series' => [],
                'topPages' => [],
            ]);
        }

        return $this->jsonSuccess([
            'available' => true,
            'period' => $period,
            'hostname' => $tenant->publicHostname(),
            ...$overview,
        ]);
    }
}
