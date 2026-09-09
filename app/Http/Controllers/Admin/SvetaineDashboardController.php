<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\TenantType;
use App\Http\Controllers\AdminController;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;

class SvetaineDashboardController extends AdminController
{
    public function __construct(
        public Authorizer $authorizer,
    ) {}

    public function svetaine()
    {
        $this->handleAuthorization('viewAny', Page::class);

        $selectedTenant = request()->input('tenant_id');

        // Leave only tenants that are not 'pkp'
        $tenants = collect(GetTenantsForUpserts::execute('pages.update.padalinys', $this->authorizer))->filter(fn ($tenant) => in_array($tenant['type'], TenantType::representationalValues(), true))->values();

        // Check if selected tenant is in the list of tenants
        if ($selectedTenant) {
            $selectedTenant = $tenants->firstWhere('id', $selectedTenant);
        } else {
            // Check if there's tenant with type 'pagrindinis'
            $selectedTenant = $tenants->firstWhere('type', TenantType::Pagrindinis->value);
        }

        // If not, select first tenant
        if (! $selectedTenant) {
            $selectedTenant = $tenants->first();
        }

        /**
         * Only identity is needed: the page's content counters were replaced by the
         * Umami traffic section, which is fetched client-side and keyed on the tenant id.
         */
        $providedTenant = $selectedTenant
            ? Tenant::query()->find($selectedTenant['id'], ['id', 'alias', 'shortname', 'type'])
            : null;

        return $this->inertiaResponse('Admin/Dashboard/ShowSvetaine', [
            'tenants' => $tenants,
            'providedTenant' => $providedTenant,
        ]);
    }
}
