<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\GoalStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Goals\LinkGoalProblemRequest;
use App\Http\Requests\Goals\StoreGoalRequest;
use App\Http\Requests\Goals\UpdateGoalRequest;
use App\Http\Resources\StepResource;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Goal;
use App\Models\Problem;
use App\Services\AgendaItemPresenter;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\Experiments\GoalsExperiment;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Goals pilot (App\Support\Experiments\GoalsExperiment); every route here sits behind the
 * `experiment.goals` middleware.
 */
class GoalController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Goals are a handful per padalinys a year, so the whole list is sent and filtered in the browser.
     */
    public function index(Request $request): Response
    {
        $this->handleAuthorization('viewAny', Goal::class);

        $user = $request->user();

        $goals = Goal::query()
            ->whereHas('tenant', fn ($query) => $query->where('goals_enabled', true))
            ->with(['tenant:id,shortname', 'cadence', 'responsibleDuty:id,name'])
            ->withCount(['steps', 'problems'])
            ->latest()
            ->get()
            ->map(fn (Goal $goal): array => [
                ...$this->summary($goal),
                'steps_count' => $goal->steps_count,
                'problems_count' => $goal->problems_count,
                'can_update' => $user->can('update', $goal),
            ]);

        return $this->inertiaResponse('Admin/Goals/IndexGoal', [
            'goals' => $goals,
            'canCreate' => $user->can('create', Goal::class),
        ]);
    }

    public function create(): Response
    {
        $this->handleAuthorization('create', Goal::class);

        $tenants = GetTenantsForUpserts::execute('goals.create.padalinys', $this->authorizer)
            ->whereIn('id', GoalsExperiment::enabledTenantIds())->values();

        return $this->inertiaResponse('Admin/Goals/CreateGoal', [
            ...$this->formOptions($tenants),
            'goal' => [
                'tenant_id' => $tenants->count() === 1 ? $tenants->first()['id'] : null,
                'cadence_id' => Cadence::query()->globalLadder()->containing(now()->toDateString())->value('id'),
            ],
        ]);
    }

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $goal = Goal::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('goals.show', $goal)->with('success', $this->entityMessage('created', 'goal'));
    }

    public function show(Request $request, Goal $goal): Response
    {
        $this->handleAuthorization('view', $goal);

        $user = $request->user();
        $goal->load(['tenant', 'cadence', 'responsibleDuty.current_users', 'createdBy:id,name']);

        return $this->inertiaResponse('Admin/Goals/ShowGoal', [
            'goal' => [
                ...$goal->toFullArray(),
                'status' => $goal->status->value,
                'tenant' => $goal->tenant->only(['id', 'shortname']),
                'cadence' => $goal->cadence?->only(['id', 'label']),
                'responsible_duty' => $goal->responsibleDuty === null ? null : [
                    'id' => $goal->responsibleDuty->id,
                    'name' => $goal->responsibleDuty->name,
                    'holders' => $goal->responsibleDuty->current_users->pluck('name')->values(),
                ],
                'created_by' => $goal->createdBy?->only(['id', 'name']),
                'public_url' => $goal->is_public ? $this->publicUrl($goal) : null,
            ],
            'steps' => StepResource::collection($goal->steps()->with(StepResource::RELATIONS)->get()
                ->filter(fn ($step) => ! $step->agendaItem?->is_private || AgendaItemPresenter::canRead($step->agendaItem, request()->user()))
                ->values())->resolve(),
            'problems' => $goal->problems()->with('tenant:id,shortname')->get()->map(fn (Problem $problem): array => [
                'id' => $problem->id,
                'title' => $problem->title,
                'status' => $problem->status,
                'tenant' => $problem->tenant->shortname,
            ]),
            // Only needed once someone opens the picker.
            'problemOptions' => Inertia::optional(fn (): Collection => Problem::query()
                ->with('tenant:id,shortname')
                ->whereNotIn('id', $goal->problems()->select('problems.id'))
                ->latest('occurred_at')
                ->get(['id', 'title', 'tenant_id', 'status'])
                ->map(fn (Problem $problem): array => [
                    'id' => $problem->id,
                    'title' => $problem->title,
                    'tenant' => $problem->tenant->shortname,
                ])),
            'can' => [
                'update' => $user->can('update', $goal),
                'delete' => $user->can('delete', $goal),
            ],
        ]);
    }

    public function edit(Goal $goal): Response
    {
        $this->handleAuthorization('update', $goal);

        return $this->inertiaResponse('Admin/Goals/EditGoal', [
            ...$this->formOptions(GetTenantsForUpserts::execute('goals.update.padalinys', $this->authorizer)
                ->whereIn('id', GoalsExperiment::enabledTenantIds())->values()),
            'goal' => [
                ...$goal->toFullArray(),
                'status' => $goal->status->value,
            ],
        ]);
    }

    public function update(UpdateGoalRequest $request, Goal $goal): RedirectResponse
    {
        $goal->update($request->validated());

        return back()->with('success', $this->entityMessage('updated', 'goal'));
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->handleAuthorization('delete', $goal);

        $goal->delete();

        return $this->redirectToIndexWithInfo('goals', $this->entityMessage('deleted', 'goal'));
    }

    public function linkProblem(LinkGoalProblemRequest $request, Goal $goal): RedirectResponse
    {
        $goal->problems()->syncWithoutDetaching([$request->validated('problem_id')]);

        return back()->with('success', __('goals.messages.problem_linked'));
    }

    public function unlinkProblem(Goal $goal, Problem $problem): RedirectResponse
    {
        $this->handleAuthorization('update', $goal);

        $goal->problems()->detach($problem->id);

        return back()->with('success', __('goals.messages.problem_unlinked'));
    }

    /**
     * @param  Collection<int, array{id: int, shortname: string, type: string|null}>  $tenants
     * @return array<string, mixed>
     */
    private function formOptions(Collection $tenants): array
    {
        return [
            'tenants' => $tenants,
            'cadences' => Cadence::query()->globalLadder()->orderByDesc('start_date')->get()
                ->map(fn (Cadence $cadence): array => $cadence->only(['id', 'label'])),
            'statuses' => collect(GoalStatus::cases())->map(fn (GoalStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'duties' => Duty::query()
                ->with('institution:id,name,tenant_id')
                ->whereHas('institution', fn ($query) => $query->whereIn('tenant_id', $tenants->pluck('id')))
                ->get(['id', 'name', 'institution_id'])
                ->map(fn (Duty $duty): array => [
                    'id' => $duty->id,
                    'name' => $duty->name,
                    'institution' => $duty->institution?->name,
                    'tenant_id' => $duty->institution?->tenant_id,
                ])
                ->sortBy('name')
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'title' => $goal->getTranslations('title'),
            'status' => $goal->status->value,
            'is_public' => $goal->is_public,
            'tenant' => $goal->tenant->only(['id', 'shortname']),
            'cadence' => $goal->cadence?->only(['id', 'label']),
            'responsible_duty' => $goal->responsibleDuty?->name,
            'updated_at' => $goal->updated_at?->toISOString(),
        ];
    }

    private function publicUrl(Goal $goal): string
    {
        return LocalizedRouteSlugs::route('publicGoals.show', [
            'subdomain' => $goal->tenant->subdomain(),
            'goal' => $goal->id,
        ], 'lt');
    }
}
