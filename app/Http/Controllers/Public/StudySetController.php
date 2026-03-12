<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\StudySet;
use App\Models\Tenant;
use Inertia\Inertia;

class StudySetController extends PublicController
{
    public function index()
    {
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('studySets');

        $seo = $this->shareAndReturnSEOObject(
            contentTenant: null,
            title: __('studySets.page_title'),
            description: __('studySets.page_description')
        );

        $tenants = Tenant::query()
            ->where('type', 'padalinys')
            ->orderBy('shortname_vu')
            ->get(['id', 'shortname', 'alias', 'shortname_vu']);

        $studySets = StudySet::query()
            ->where('is_visible', true)
            ->with([
                'tenant:id,shortname,alias,shortname_vu',
                'courses' => fn ($query) => $query->where('is_visible', true)->orderBy('order'),
                'courses.reviews' => fn ($query) => $query->where('is_visible', true),
            ])
            ->orderBy('order')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn ($sets) => $sets->map(fn (StudySet $set) => [
                ...$set->toArray(),
                'total_credits' => $set->courses->sum('credits'),
                'updated_at' => $set->updated_at->translatedFormat('Y-m-d'),
                'courses' => $set->courses->map(fn ($course) => [
                    ...$course->toArray(),
                    'reviews' => $course->reviews->map->toArray(),
                ]),
            ]));

        return Inertia::render('Public/ShowStudySets', [
            'tenants' => $tenants,
            'studySetsByTenant' => $studySets,
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }
}
