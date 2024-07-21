<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function representativesInTenant()
    {
        // get all tenants, get institutions->duties where duty type is studentu atstovas and count how many users are in those tenants

        // get all tenants
        $tenants = Tenant::with(['institutions' => function ($query) {
            $query->select('id', 'tenant_id')->withCount('meetings')->with(['duties' => function ($query) {
                $query->select('id', 'institution_id')->whereHas('types', function ($query) {
                    $query->where('slug', 'studentu-atstovai');
                })->with('users');
            }]);
        }])->orderBy('shortname')->get();

        $tenants->map(function ($tenant) {
            $tenant->institutions->map(function ($institution) {
                $institution->duties->map(function ($duty) {
                    $duty->users;
                });
            });
        });

        // flatten users to tenant
        $tenants->each(function ($tenant) {
            $tenant->users = new Collection($tenant->institutions->pluck('duties')->flatten()->pluck('users')->flatten()->unique('id')->values());

            $tenant->users->makeVisible(['last_action']);
        });

        // calculate stats for each tenant

        return Inertia::render('Admin/Stats/RepresentativesInTenants', [
            'tenants' => $tenants->toArray(),
        ]);
    }
}
