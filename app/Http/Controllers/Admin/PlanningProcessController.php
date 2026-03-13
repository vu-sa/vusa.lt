<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexPlanningProcessRequest;
use App\Http\Requests\StorePlanningProcessRequest;
use App\Http\Requests\UpdatePlanningProcessRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\PlanningProcess;
use App\Models\PlanningStageDeadline;
use App\Models\Problem;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PlanningProcessController extends AdminController
{
    use HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private TanstackTableService $tableService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexPlanningProcessRequest $request): Response
    {
        $this->handleAuthorization('viewAny', PlanningProcess::class);

        // Base query with permission filtering (used for both stats and table)
        $baseQuery = PlanningProcess::query();
        $baseQuery = $this->tableService->applyPermissionFiltering(
            $baseQuery,
            'tenant',
            'planningProcesses.read.padalinys',
            $this->authorizer
        );

        // Summary statistics (unfiltered, scoped to permission)
        $summary = $this->buildSummaryStats(clone $baseQuery);

        // Table query with eager loading
        $query = (clone $baseQuery)->with(['tenant', 'moderator']);

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            [],
            ['applySortBeforePagination' => true]
        );

        $filters = $request->getFilters();

        if (isset($filters['current_stage']) && ! empty($filters['current_stage'])) {
            $query->whereIn('current_stage', (array) $filters['current_stage']);
        }

        if (isset($filters['academic_year_start']) && ! empty($filters['academic_year_start'])) {
            $query->whereIn('academic_year_start', (array) $filters['academic_year_start']);
        }

        if (isset($filters['tenant_id']) && ! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['needs_confirmation']) && $filters['needs_confirmation']) {
            $query->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereNotNull('goal_text')->whereNull('goal_approved_at');
                })->orWhere(function ($q) {
                    $q->whereNull('tip_approved_at')
                        ->whereHas('media', fn ($m) => $m->where('collection_name', 'tip_document'));
                })->orWhere(function ($q) {
                    $q->whereNull('mvp_approved_at')
                        ->whereHas('media', fn ($m) => $m->where('collection_name', 'mvp_document'));
                });
            })->where('current_stage', '<=', 5);
        }

        $planningProcesses = $query->paginate($request->input('per_page', 20))->withQueryString();

        $items = collect($planningProcesses->items())->map(function (PlanningProcess $process) {
            $array = $process->toArray();
            $array['needs_confirmation'] = $process->needsCoordinatorAction();

            return $array;
        })->all();

        return $this->inertiaResponse('Admin/PlanningProcesses/IndexPlanningProcess', [
            'data' => $items,
            'summary' => $summary,
            'meta' => [
                'total' => $planningProcesses->total(),
                'per_page' => $planningProcesses->perPage(),
                'current_page' => $planningProcesses->currentPage(),
                'last_page' => $planningProcesses->lastPage(),
                'from' => $planningProcesses->firstItem(),
                'to' => $planningProcesses->lastItem(),
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->boolean('showDeleted', false),
            'tenants' => Tenant::select('id', 'fullname', 'shortname')->orderBy('shortname')->get()->map->toArray(),
        ]);
    }

    /**
     * Build summary statistics for the coordinator overview.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PlanningProcess>  $query
     * @return array<string, int>
     */
    private function buildSummaryStats($query): array
    {
        $processes = $query->get();

        $stageCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $finished = 0;
        $needsConfirmation = 0;
        $noModerator = 0;

        foreach ($processes as $process) {
            if ($process->isFinished()) {
                $finished++;
            } elseif (isset($stageCounts[$process->current_stage])) {
                $stageCounts[$process->current_stage]++;
            }

            if ($process->needsCoordinatorAction()) {
                $needsConfirmation++;
            }

            if (is_null($process->moderator_user_id)) {
                $noModerator++;
            }
        }

        return [
            'total' => $processes->count(),
            'finished' => $finished,
            'in_progress' => $processes->count() - $finished,
            'needs_confirmation' => $needsConfirmation,
            'no_moderator' => $noModerator,
            'stage_counts' => $stageCounts,
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->handleAuthorization('create', PlanningProcess::class);

        $tenants = GetTenantsForUpserts::execute('planningProcesses.create.padalinys', $this->authorizer);

        return $this->inertiaResponse('Admin/PlanningProcesses/CreatePlanningProcess', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlanningProcessRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $planningProcess = PlanningProcess::create($validated);

        return $this->redirectToIndexWithSuccess(
            'planningProcesses',
            trans_choice('messages.created', 0, ['model' => trans_choice('entities.planningProcess.model', 1)])
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(PlanningProcess $planningProcess): Response
    {
        $this->handleAuthorization('view', $planningProcess);

        $user = auth()->user();

        $planningProcess->load([
            'tenant',
            'moderator',
            'selectedProblem',
            'tipApprovedByUser',
            'mvpApprovedByUser',
            'activities',
            'monitoringEntries.submittedBy',
        ]);

        $tipDocument = $planningProcess->getFirstMedia('tip_document');
        $mvpDocument = $planningProcess->getFirstMedia('mvp_document');
        $tipTemplate = $planningProcess->getFirstMedia('tip_template');
        $mvpTemplate = $planningProcess->getFirstMedia('mvp_template');

        $deadlines = PlanningStageDeadline::where('academic_year_start', $planningProcess->academic_year_start)
            ->orderBy('stage')
            ->get();

        return $this->inertiaResponse('Admin/PlanningProcesses/ShowPlanningProcess', [
            'planningProcess' => [
                ...$planningProcess->toArray(),
                'tip_document_url' => $tipDocument?->getUrl(),
                'tip_document_name' => $tipDocument?->file_name,
                'mvp_document_url' => $mvpDocument?->getUrl(),
                'mvp_document_name' => $mvpDocument?->file_name,
                'tip_template_url' => $tipTemplate?->getUrl(),
                'tip_template_name' => $tipTemplate?->file_name,
                'mvp_template_url' => $mvpTemplate?->getUrl(),
                'mvp_template_name' => $mvpTemplate?->file_name,
            ],
            'deadlines' => $deadlines->toArray(),
            'tenantProblems' => Problem::where('tenant_id', $planningProcess->tenant_id)
                ->select('id', 'title')
                ->get()
                ->map->toArray(),
            'canUpdate' => $user->can('update', $planningProcess),
            'canDelete' => $user->can('delete', $planningProcess),
            'isFinished' => $planningProcess->isFinished(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlanningProcessRequest $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $planningProcess->update($request->validated());

        $planningProcess->advanceIfCurrentStageComplete();

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.planningProcess.model', 1)]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('delete', $planningProcess);

        $planningProcess->delete();

        return $this->redirectToIndexWithInfo(
            'planningProcesses',
            trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.planningProcess.model', 1)])
        );
    }

    /**
     * Assign a moderator to the planning process.
     */
    public function assignModerator(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('update', $planningProcess);

        $validated = $request->validate([
            'moderator_user_id' => ['nullable', 'string', 'exists:users,id'],
        ]);

        $planningProcess->update($validated);

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.planningProcess.model', 1)]));
    }

    /**
     * Update the goal text (by moderator or coordinator).
     */
    public function updateGoal(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('update', $planningProcess);

        $validated = $request->validate([
            'goal_text' => ['nullable', 'string'],
            'goal_approved_at' => ['nullable', 'date'],
        ]);

        // Goal approval requires coordinator-level permission (not the assigned moderator)
        if (array_key_exists('goal_approved_at', $validated) && $validated['goal_approved_at'] !== null) {
            $this->handleAuthorization('approve', $planningProcess);
        }

        $planningProcess->update($validated);

        $planningProcess->advanceIfCurrentStageComplete();

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.planningProcess.model', 1)]));
    }

    /**
     * Upload a PDF document (TIP or MVP).
     */
    public function uploadDocument(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('update', $planningProcess);

        $validated = $request->validate([
            'collection' => ['required', 'string', 'in:tip_document,mvp_document'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $planningProcess
            ->addMediaFromRequest('document')
            ->toMediaCollection($validated['collection']);

        return back()->with('success', __('planning.document_uploaded'));
    }

    /**
     * Approve a document (coordinator only).
     */
    public function approveDocument(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('approve', $planningProcess);

        $validated = $request->validate([
            'collection' => ['required', 'string', 'in:tip_document,mvp_document'],
        ]);

        $user = auth()->user();

        if ($validated['collection'] === 'tip_document') {
            $planningProcess->update([
                'tip_approved_at' => now(),
                'tip_approved_by' => $user->id,
            ]);
        } else {
            $planningProcess->update([
                'mvp_approved_at' => now(),
                'mvp_approved_by' => $user->id,
            ]);
        }

        $planningProcess->refresh();
        $planningProcess->advanceIfCurrentStageComplete();

        return back()->with('success', __('planning.document_approved'));
    }

    /**
     * Upload a template file (TIP or MVP) for coordinators.
     */
    public function uploadTemplate(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('update', $planningProcess);

        $validated = $request->validate([
            'collection' => ['required', 'string', 'in:tip_template,mvp_template'],
            'template' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:20480'],
        ]);

        $planningProcess
            ->addMediaFromRequest('template')
            ->toMediaCollection($validated['collection']);

        return back()->with('success', __('planning.template_uploaded'));
    }

    /**
     * Delete a template file (TIP or MVP).
     */
    public function deleteTemplate(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('update', $planningProcess);

        $validated = $request->validate([
            'collection' => ['required', 'string', 'in:tip_template,mvp_template'],
        ]);

        $planningProcess->clearMediaCollection($validated['collection']);

        return back()->with('success', __('planning.template_deleted'));
    }

    /**
     * Advance the planning process to the next stage.
     */
    public function advanceStage(PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('approve', $planningProcess);

        $nextStage = $planningProcess->current_stage + 1;

        if ($nextStage > 6) {
            return back()->with('error', __('planning.already_finished'));
        }

        if (! $planningProcess->canAdvanceToStage($nextStage)) {
            return back()->with('error', __('planning.stage_not_complete'));
        }

        $updateData = ['current_stage' => $nextStage];

        // Lock the process when finishing
        if ($nextStage > 5) {
            $updateData['locked_at'] = now();
        }

        $planningProcess->update($updateData);

        return back()->with('success', __('planning.stage_advanced'));
    }
}
