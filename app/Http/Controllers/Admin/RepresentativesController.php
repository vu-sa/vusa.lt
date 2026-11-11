<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Http\Requests\ShowRepresentativesRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\LearningPathPresenter;
use App\Services\RepresentativesPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RepresentativesController extends AdminController
{
    public function index(ShowRepresentativesRequest $request): Response
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $visible = RepresentativesPresenter::visibleTenantIds($viewer);
        $requested = collect($request->validated('tenants') ?? [])->map(fn ($id): int => (int) $id)->intersect($visible)->values();
        $selected = ($requested->isEmpty() ? $visible : $requested)->all();

        return Inertia::render('Admin/Dashboard/ShowAtstovai', [
            'tenants' => AtstovavimasDashboardController::tenantOptions($visible)->values()->all(),
            'selectedTenantIds' => array_map(strval(...), $selected),
            'overview' => Inertia::defer(fn () => RepresentativesPresenter::overview($viewer, $selected, app()->getLocale()), 'reps'),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $this->authorize('viewRepresentative', $user);
        $rep = RepresentativesPresenter::representative($viewer, $user, app()->getLocale());
        abort_if($rep === null, 404);
        $seesLearning = RepresentativesPresenter::seesLearningOf($viewer, $user);

        return Inertia::render('Admin/Representation/ShowRepresentative', [
            'rep' => $rep,
            'path' => $seesLearning ? LearningPathPresenter::path(ResolveLearningPath::execute($user), app()->getLocale()) : null,
            'tasks' => Task::openTaskCountsFor($user),
            'canViewMember' => $viewer->can('view', $user),
        ]);
    }
}
