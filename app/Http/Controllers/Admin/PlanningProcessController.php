<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\ApprovalDecision;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexPlanningProcessRequest;
use App\Http\Requests\StorePlanningProcessRequest;
use App\Http\Requests\UpdatePlanningProcessRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Approval;
use App\Models\Duty;
use App\Models\PlanningProcess;
use App\Models\PlanningResource;
use App\Models\PlanningStageDeadline;
use App\Models\Problem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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
            'planningResources' => PlanningResource::ordered()
                ->get()
                ->map(function (PlanningResource $resource) {
                    $data = $resource->toArray();
                    $media = $resource->getFirstMedia('resource_file');
                    $data['file_url'] = $media?->getUrl();
                    $data['file_name'] = $media?->file_name;

                    return $data;
                }),
            'canManageResources' => $this->authorizer->forUser(auth()->user())->check('planningProcesses.update.padalinys'),
            'resourceAcademicYears' => PlanningProcess::query()
                ->select('academic_year_start')
                ->distinct()
                ->orderByDesc('academic_year_start')
                ->pluck('academic_year_start'),
        ]);
    }

    /**
     * Build summary statistics for the coordinator overview.
     *
     * @param  Builder<PlanningProcess>  $query
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

        // Auto-assign users with pirmininkas duty type as editors
        $pirmininkasTypeId = Type::where('slug', 'pirmininkas')
            ->where('model_type', Duty::class)
            ->value('id');

        if ($pirmininkasTypeId) {
            $userIds = User::whereHas('current_duties', function ($q) use ($planningProcess, $pirmininkasTypeId) {
                $q->whereHas('institution', function ($iq) use ($planningProcess) {
                    $iq->whereHas('tenant', function ($tq) use ($planningProcess) {
                        $tq->where('tenants.id', $planningProcess->tenant_id);
                    });
                })->whereHas('types', function ($tq) use ($pirmininkasTypeId) {
                    $tq->where('types.id', $pirmininkasTypeId);
                });
            })->pluck('id');

            $planningProcess->editors()->syncWithoutDetaching($userIds);
        }

        return $this->redirectToIndexWithSuccess(
            'planavimai',
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

        $isModerator = $planningProcess->moderator_user_id === $user->id;

        $planningProcess->load([
            'tenant',
            'moderator',
            'editors',
            'selectedProblem',
            'tipApprovedByUser',
            'mvpApprovedByUser',
            'tipApprovedMedia',
            'mvpApprovedMedia',
            'activities',
            'monitoringEntries.submittedBy',
            'approvals.user',
            'comments' => fn ($q) => $q->whereNotNull('stage')->orderBy('created_at'),
        ]);

        // All document versions (not just the latest)
        $tipDocuments = $planningProcess->getMedia('tip_document');
        $mvpDocuments = $planningProcess->getMedia('mvp_document');
        $deadlines = PlanningStageDeadline::where('academic_year_start', $planningProcess->academic_year_start)
            ->orderBy('stage')
            ->get();

        // Approval history grouped by context
        $approvalHistory = $planningProcess->approvals
            ->groupBy('context')
            ->map(fn ($approvals) => $approvals->sortByDesc('created_at')->values()->map(fn (Approval $a) => [
                'id' => $a->id,
                'decision' => $a->decision->value,
                'notes' => $a->notes,
                'created_at' => $a->created_at->toISOString(),
                'user' => $a->user ? [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                    'profile_photo_path' => $a->user->profile_photo_path ?? null,
                ] : null,
            ])->all());

        // Latest document URL (last in collection)
        $latestTip = $tipDocuments->last();
        $latestMvp = $mvpDocuments->last();

        $isEditor = $planningProcess->isEditor($user);
        $isCoordinator = $user->can('approve', $planningProcess);

        // Field change history — only visible to coordinator, editor, moderator
        $canViewFieldChanges = $isCoordinator || $isEditor || $isModerator;
        $fieldChanges = [];

        if ($canViewFieldChanges) {
            $stage1Fields = ['expectations_text', 'expectations_submitted_at'];
            $userIdFields = ['moderator_user_id', 'tip_approved_by', 'mvp_approved_by'];

            $activities = Activity::where('subject_type', PlanningProcess::class)
                ->where('subject_id', $planningProcess->id)
                ->whereNotNull('properties->old')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            // Collect all user IDs referenced in changes for batch resolution
            $userIds = collect();
            foreach ($activities as $a) {
                foreach (['old', 'attributes'] as $prop) {
                    $values = $a->properties[$prop] ?? [];
                    foreach ($userIdFields as $field) {
                        if (! empty($values[$field])) {
                            $userIds->push($values[$field]);
                        }
                    }
                }
            }
            /** @var Collection<string, string> $userNames */
            $userNames = User::whereIn('id', $userIds->unique()->filter())->pluck('name', 'id');

            $fieldChanges = $activities
                ->map(function (Activity $a) use ($isModerator, $isCoordinator, $stage1Fields, $userIdFields, $userNames) {
                    $old = $a->properties['old'] ?? [];
                    $new = $a->properties['attributes'] ?? [];

                    // Hide stage 1 fields from moderators (who aren't also coordinators)
                    if ($isModerator && ! $isCoordinator) {
                        $old = array_diff_key($old, array_flip($stage1Fields));
                        $new = array_diff_key($new, array_flip($stage1Fields));

                        if (empty($new)) {
                            return null;
                        }
                    }

                    // Resolve user IDs to names for human-readable display
                    foreach ($userIdFields as $field) {
                        if (isset($old[$field]) && $old[$field]) {
                            $old[$field] = $userNames[$old[$field]] ?? $old[$field];
                        }
                        if (isset($new[$field]) && $new[$field]) {
                            $new[$field] = $userNames[$new[$field]] ?? $new[$field];
                        }
                    }

                    return [
                        'id' => $a->id,
                        'description' => $a->description,
                        'old' => $old,
                        'new' => $new,
                        'created_at' => $a->created_at->toISOString(),
                        'causer_name' => $a->causer?->name ?? null,
                    ];
                })->filter()->values()->all();
        }

        // Only coordinators and editors can see expectations text
        $canViewExpectations = $isCoordinator || $isEditor;

        $planningProcessData = $planningProcess->toArray();
        if (! $canViewExpectations) {
            $planningProcessData['expectations_text'] = null;
        }

        return $this->inertiaResponse('Admin/PlanningProcesses/ShowPlanningProcess', [
            'planningProcess' => [
                ...$planningProcessData,
                'tip_document_url' => $latestTip?->getUrl(),
                'tip_document_name' => $latestTip?->file_name,
                'mvp_document_url' => $latestMvp?->getUrl(),
                'mvp_document_name' => $latestMvp?->file_name,
                'tip_approved_media_id' => $planningProcess->tip_approved_media_id,
                'mvp_approved_media_id' => $planningProcess->mvp_approved_media_id,
            ],
            'tipDocuments' => $tipDocuments->map(fn ($media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->size,
                'created_at' => $media->created_at->toISOString(),
            ])->values()->all(),
            'mvpDocuments' => $mvpDocuments->map(fn ($media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->size,
                'created_at' => $media->created_at->toISOString(),
            ])->values()->all(),
            'approvalHistory' => $approvalHistory->all(),
            'fieldChanges' => $fieldChanges,
            'deadlines' => $deadlines->toArray(),
            'tenantProblems' => Problem::where('tenant_id', $planningProcess->tenant_id)
                ->select('id', 'title')
                ->get()
                ->map->toArray(),
            'stageComments' => $planningProcess->comments
                ->groupBy('stage')
                ->mapWithKeys(fn ($comments, $stage) => [(int) $stage => $comments->toArray()]),
            'editors' => $planningProcess->editors->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all(),
            'canUpdate' => $user->can('update', $planningProcess),
            'canApprove' => $isCoordinator,
            'canDelete' => $user->can('delete', $planningProcess),
            'canManageEditors' => $user->can('manageEditors', $planningProcess),
            'canAssignModerator' => $user->can('assignModerator', $planningProcess),
            'planningResources' => PlanningResource::forAcademicYear($planningProcess->academic_year_start)
                ->ordered()
                ->get()
                ->map(function (PlanningResource $resource) {
                    $data = $resource->toArray();
                    $media = $resource->getFirstMedia('resource_file');
                    $data['file_url'] = $media?->getUrl();
                    $data['file_name'] = $media?->file_name;

                    return $data;
                }),
            'canViewExpectations' => $canViewExpectations,
            'canViewFieldChanges' => $canViewFieldChanges,
            'isModerator' => $isModerator,
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
            'planavimai',
            trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.planningProcess.model', 1)])
        );
    }

    /**
     * Assign a moderator to the planning process.
     */
    public function assignModerator(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('assignModerator', $planningProcess);

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

            // Create an approval record for the goal
            Approval::create([
                'approvable_type' => PlanningProcess::class,
                'approvable_id' => $planningProcess->id,
                'user_id' => auth()->id(),
                'decision' => ApprovalDecision::Approved,
                'step' => 1,
                'context' => 'goal',
            ]);
        }

        // If goal text is being changed and the goal was previously approved, require reapproval
        if (array_key_exists('goal_text', $validated)
            && $validated['goal_text'] !== $planningProcess->goal_text
            && $planningProcess->goal_approved_at !== null
            && ! (array_key_exists('goal_approved_at', $validated) && $validated['goal_approved_at'] !== null)
        ) {
            $validated['goal_approved_at'] = null;
        }

        $planningProcess->update($validated);

        $planningProcess->advanceIfCurrentStageComplete();

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.planningProcess.model', 1)]));
    }

    /**
     * Reject the goal (coordinator only).
     */
    public function rejectGoal(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('approve', $planningProcess);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        Approval::create([
            'approvable_type' => PlanningProcess::class,
            'approvable_id' => $planningProcess->id,
            'user_id' => auth()->id(),
            'decision' => ApprovalDecision::Rejected,
            'step' => 1,
            'context' => 'goal',
            'notes' => $validated['notes'],
        ]);

        $planningProcess->update(['goal_approved_at' => null]);

        return back()->with('success', __('planning.goal_rejected'));
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

        $collection = $validated['collection'];

        $planningProcess
            ->addMediaFromRequest('document')
            ->toMediaCollection($collection);

        // If the document was previously approved, require reapproval
        if ($collection === 'tip_document' && $planningProcess->tip_approved_at !== null) {
            $planningProcess->update([
                'tip_approved_at' => null,
                'tip_approved_by' => null,
                'tip_approved_media_id' => null,
            ]);
        } elseif ($collection === 'mvp_document' && $planningProcess->mvp_approved_at !== null) {
            $planningProcess->update([
                'mvp_approved_at' => null,
                'mvp_approved_by' => null,
                'mvp_approved_media_id' => null,
            ]);
        }

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
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();
        $collection = $validated['collection'];

        // Get the latest document in this collection
        $latestMedia = $planningProcess->getMedia($collection)->last();

        // Create an approval record
        Approval::create([
            'approvable_type' => PlanningProcess::class,
            'approvable_id' => $planningProcess->id,
            'user_id' => $user->id,
            'decision' => ApprovalDecision::Approved,
            'step' => 1,
            'context' => $collection,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update timestamp fields and approved media ID
        if ($collection === 'tip_document') {
            $planningProcess->update([
                'tip_approved_at' => now(),
                'tip_approved_by' => $user->id,
                'tip_approved_media_id' => $latestMedia?->id,
            ]);
        } else {
            $planningProcess->update([
                'mvp_approved_at' => now(),
                'mvp_approved_by' => $user->id,
                'mvp_approved_media_id' => $latestMedia?->id,
            ]);
        }

        $planningProcess->refresh();
        $planningProcess->advanceIfCurrentStageComplete();

        return back()->with('success', __('planning.document_approved'));
    }

    /**
     * Reject a document with feedback (coordinator only).
     */
    public function rejectDocument(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('approve', $planningProcess);

        $validated = $request->validate([
            'collection' => ['required', 'string', 'in:tip_document,mvp_document'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $collection = $validated['collection'];

        Approval::create([
            'approvable_type' => PlanningProcess::class,
            'approvable_id' => $planningProcess->id,
            'user_id' => auth()->id(),
            'decision' => ApprovalDecision::Rejected,
            'step' => 1,
            'context' => $collection,
            'notes' => $validated['notes'],
        ]);

        // Clear approval so moderator can re-upload
        if ($collection === 'tip_document') {
            $planningProcess->update([
                'tip_approved_at' => null,
                'tip_approved_by' => null,
                'tip_approved_media_id' => null,
            ]);
        } else {
            $planningProcess->update([
                'mvp_approved_at' => null,
                'mvp_approved_by' => null,
                'mvp_approved_media_id' => null,
            ]);
        }

        return back()->with('success', __('planning.document_rejected'));
    }

    /**
     * Add an editor to the planning process.
     */
    public function addEditor(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('manageEditors', $planningProcess);

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
        ]);

        if ($validated['user_id'] === $planningProcess->moderator_user_id) {
            return back()->with('error', __('planning.editor_is_moderator'));
        }

        $planningProcess->editors()->syncWithoutDetaching([$validated['user_id']]);

        return back()->with('success', __('planning.editor_added'));
    }

    /**
     * Remove an editor from the planning process.
     */
    public function removeEditor(Request $request, PlanningProcess $planningProcess): RedirectResponse
    {
        $this->handleAuthorization('manageEditors', $planningProcess);

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
        ]);

        $planningProcess->editors()->detach($validated['user_id']);

        return back()->with('success', __('planning.editor_removed'));
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

    /**
     * Download the latest TIP and MVP documents from all padaliniai as a ZIP.
     */
    public function downloadDocuments(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->handleAuthorization('viewAny', PlanningProcess::class);

        $defaultYear = PlanningProcess::max('academic_year_start') ?? (now()->month >= 9 ? now()->year : now()->year - 1);
        $academicYear = $request->integer('academic_year_start', $defaultYear);

        $query = PlanningProcess::query()
            ->where('academic_year_start', $academicYear)
            ->with('tenant');

        $query = $this->tableService->applyPermissionFiltering(
            $query,
            'tenant',
            'planningProcesses.read.padalinys',
            $this->authorizer
        );

        $processes = $query->get();

        // Collect all documents first to check if there are any
        $documents = [];

        foreach ($processes as $process) {
            $tenantName = $process->tenant?->shortname ?? 'Unknown';

            $latestTip = $process->getMedia('tip_document')->last();
            $latestMvp = $process->getMedia('mvp_document')->last();

            if ($latestTip) {
                $documents[] = ['path' => $latestTip->getPath(), 'name' => "{$tenantName}/TIP.pdf"];
            }

            if ($latestMvp) {
                $documents[] = ['path' => $latestMvp->getPath(), 'name' => "{$tenantName}/MVP.pdf"];
            }
        }

        if (empty($documents)) {
            return back()->with('error', __('planning.no_documents_to_download'));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'planning_docs_').'.zip';

        $zip = new ZipArchive;

        if ($zip->open($tempPath, ZipArchive::CREATE) !== true) {
            return back()->with('error', __('planning.no_documents_to_download'));
        }

        foreach ($documents as $doc) {
            $zip->addFile($doc['path'], $doc['name']);
        }

        $zip->close();

        $fileName = "planavimo_dokumentai_{$academicYear}-".($academicYear + 1).'.zip';

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
