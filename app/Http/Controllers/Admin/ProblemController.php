<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildProblemIndexQuery;
use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexProblemRequest;
use App\Http\Requests\StoreProblemRequest;
use App\Http\Requests\UpdateProblemRequest;
use App\Http\Requests\UpdateProblemStatusRequest;
use App\Http\Resources\StepResource;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\ProblemCategory;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use App\Support\Experiments\GoalsExperiment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProblemController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private TanstackTableService $tableService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexProblemRequest $request)
    {
        $this->handleAuthorization('viewAny', Problem::class);

        $query = BuildProblemIndexQuery::execute($request);

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            ['title', 'description'],
            ['applySortBeforePagination' => true]
        );

        $deletedCount = $this->getTrashedCount($query);

        $problems = $query->paginate($request->getPerPage())->withQueryString();

        return $this->inertiaResponse('Admin/Problems/IndexProblem', [
            'data' => $problems->getCollection()->map(fn (Problem $problem): array => [
                ...$problem->toArray(),
                'can_update' => $request->user()->can('update', $problem),
            ])->values(),
            'meta' => [
                'total' => $problems->total(),
                'per_page' => $problems->perPage(),
                'current_page' => $problems->currentPage(),
                'last_page' => $problems->lastPage(),
                'from' => $problems->firstItem(),
                'to' => $problems->lastItem(),
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'institutions' => Institution::select('id', 'name')->orderBy('name')->get()->map(fn ($institution) => $institution->toArray()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->handleAuthorization('create', Problem::class);

        $tenants = GetTenantsForUpserts::execute('problems.create.padalinys', $this->authorizer);

        // Opened from an institution's Problemos tab: start in its padalinys, already linked, if the user may write there.
        $institution = $request->filled('institution')
            ? Institution::query()->find($request->string('institution')->toString())
            : null;
        $prefill = $institution !== null && $tenants->contains('id', $institution->tenant_id)
            ? $institution
            : null;

        return $this->inertiaResponse('Admin/Problems/CreateProblem', [
            'tenants' => $tenants,
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'problem' => $prefill ? ['tenant_id' => $prefill->tenant_id, 'institutions' => [$prefill->id]] : null,
            'institutions' => $prefill ? [$prefill->only(['id', 'name', 'tenant_id'])] : [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProblemRequest $request)
    {
        $validated = $request->validated();
        $categories = $validated['categories'] ?? [];
        $institutions = $validated['institutions'] ?? [];
        unset($validated['categories'], $validated['institutions']);

        $problem = Problem::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if (! empty($categories)) {
            $problem->categories()->sync($categories);
        }

        if (! empty($institutions)) {
            $problem->institutions()->sync($institutions);
        }

        return $this->redirectToIndexWithSuccess('problems', $this->entityMessage('created', 'problem'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Problem $problem)
    {
        $this->handleAuthorization('view', $problem);

        $user = auth()->user();

        return $this->inertiaResponse('Admin/Problems/ShowProblem', [
            'problem' => [
                ...$problem->load([
                    'tenant',
                    'createdBy',
                    'responsibleUser',
                    'categories',
                    'institutions',
                ])->toFullArray(),
            ],
            'agendaItems' => $this->discussedIn($problem),
            'canUpdate' => $user->can('update', $problem),
            'canDelete' => $user->can('delete', $problem),
            // Goals pilot: absent outside it, so the page renders exactly as before.
            ...(GoalsExperiment::enabledForUser($user) && GoalsExperiment::enabledForTenant($problem->tenant) ? [
                'goalsExperiment' => true,
                'goalLinks' => Inertia::defer(fn (): array => $this->goalLinks($problem)),
            ] : []),
        ]);
    }

    /**
     * @return array{goals: list<array<string, mixed>>, steps: array<int, mixed>}
     */
    private function goalLinks(Problem $problem): array
    {
        return [
            'goals' => $problem->goals()
                ->whereHas('tenant', fn ($query) => $query->where('goals_enabled', true))
                ->with('tenant:id,shortname')->get()->map(fn (Goal $goal): array => [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'status' => $goal->status->value,
                    'tenant' => $goal->tenant->shortname,
                ])->values()->all(),
            'steps' => StepResource::collection($problem->steps()
                ->where(fn ($query) => $query->whereNull('goal_id')
                    ->orWhereHas('goal.tenant', fn ($query) => $query->where('goals_enabled', true)))
                ->with(StepResource::RELATIONS)->get())->resolve(),
        ];
    }

    /**
     * The agenda items where the problem was raised, newest meeting first. Every member may read a
     * problem, but not every meeting, so an item the reader cannot open is left out.
     *
     * @return list<array{id: string, title: mixed, meeting_id: string, start_time: string|null, institutions: list<string>}>
     */
    private function discussedIn(Problem $problem): array
    {
        return $problem->agendaItems()
            ->with('meeting.institutions:id,name')
            ->get()
            ->filter(fn (AgendaItem $item): bool => $item->meeting !== null && Gate::allows('viewSummary', $item))
            ->sortByDesc(fn (AgendaItem $item) => $item->meeting->start_time)
            ->map(fn (AgendaItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'meeting_id' => $item->meeting_id,
                'start_time' => $item->meeting->start_time?->toISOString(),
                'institutions' => $item->meeting->institutions->pluck('name')->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Problem $problem)
    {
        $this->handleAuthorization('update', $problem);

        $problemData = $problem->load(['tenant', 'createdBy', 'responsibleUser', 'categories', 'institutions'])->toFullArray();
        $problemData['categories'] = $problem->categories->pluck('id')->toArray();
        $problemData['institutions'] = $problem->institutions->pluck('id')->toArray();

        $tenants = GetTenantsForUpserts::execute('problems.update.padalinys', $this->authorizer);

        return $this->inertiaResponse('Admin/Problems/EditProblem', [
            'problem' => $problemData,
            'tenants' => $tenants,
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'initialResponsibleUser' => $problem->responsibleUser
                ? ['id' => $problem->responsibleUser->id, 'name' => $problem->responsibleUser->name]
                : null,
            'institutions' => $problem->institutions
                ->map(fn (Institution $institution) => $institution->only(['id', 'name', 'tenant_id'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProblemRequest $request, Problem $problem)
    {
        $validated = $request->validated();
        $categories = $validated['categories'] ?? [];
        $institutions = $validated['institutions'] ?? [];
        unset($validated['categories'], $validated['institutions']);

        $problem->update($validated);

        $problem->categories()->sync($categories);
        $problem->institutions()->sync($institutions);

        return back()->with('success', $this->entityMessage('updated', 'problem'))->with('data', $problem->load(['categories', 'institutions']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem)
    {
        $this->handleAuthorization('delete', $problem);

        $problem->delete();

        return $this->redirectToIndexWithInfo('problems', $this->entityMessage('deleted', 'problem'));
    }

    /**
     * Update the status of the specified problem.
     */
    public function updateStatus(UpdateProblemStatusRequest $request, Problem $problem)
    {
        $this->handleAuthorization('update', $problem);

        $problem->update(['status' => $request->validated('status')]);

        return back()->with('success', $this->entityMessage('updated', 'problem'));
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Problem $problem): RedirectResponse
    {
        return $this->restoreModel($problem, $this->entityMessage('restored', 'problem'));
    }

    public function forceDelete(Problem $problem): RedirectResponse
    {
        return $this->forceDeleteModel($problem);
    }
}
