<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\GetUserCoordinators;
use App\Actions\ResolveLearningPath;
use App\Actions\SyncLearningAudiences;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStepKind;
use App\Http\Controllers\AdminController;
use App\Http\Requests\LearningStepRequest;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\LearningAudience;
use App\Models\LearningStep;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeBasePresenter;
use App\Services\Learning\ResolvedLearningItem;
use App\Services\LearningInstitutionContactsPresenter;
use App\Services\LearningPathPresenter;
use App\Services\ModelAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LearningStepController extends AdminController
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function index(Request $request): Response
    {
        $this->authorize('manage', LearningStep::class);

        return Inertia::render('Admin/Learning/IndexLearningStep', [
            'steps' => LearningPathPresenter::library(app()->getLocale(), $request->user()),
            'audienceTypes' => array_map(fn (LearningAudienceType $type): string => $type->value, LearningAudienceType::cases()),
            'canCreate' => $request->user()->can('create', LearningStep::class),
        ]);
    }

    public function show(Request $request, LearningStep $learningStep): Response
    {
        $this->authorize('view', $learningStep);
        $learningStep->load(['audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)]);
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->steps->concat($path->meetingPrep)->first(fn (ResolvedLearningItem $item): bool => $item->item->id === $learningStep->id) ?? new ResolvedLearningItem($learningStep, importance: $learningStep->audiences->first()->importance ?? LearningImportance::Recommended, stage: $learningStep->audiences->first()?->stage, audienceLabel: $learningStep->audiences->first()?->label(app()->getLocale()) ?? '');

        return Inertia::render('Admin/Learning/ShowLearningStep', [
            'step' => LearningPathPresenter::step($item, $request->user(), app()->getLocale()),
            'linkedPages' => KnowledgeBasePresenter::linkedPages($learningStep->getTranslation('description', app()->getLocale()), app()->getLocale()),
            'inPath' => $path->contains($learningStep),
            'coordinators' => GetUserCoordinators::execute($request->user()),
            'institutionContacts' => LearningInstitutionContactsPresenter::forUser($request->user(), app()->getLocale()),
            'canUpdate' => $request->user()->can('update', $learningStep),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LearningStep::class);

        return Inertia::render('Admin/Learning/CreateLearningStep', $this->formOptions($request->user(), 'create'));
    }

    public function edit(Request $request, LearningStep $learningStep): Response
    {
        $this->authorize('update', $learningStep);

        return Inertia::render('Admin/Learning/EditLearningStep', [
            'step' => LearningPathPresenter::edit($learningStep->load(['audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])),
            'canDelete' => $request->user()->can('delete', $learningStep),
            ...$this->formOptions($request->user(), 'update'),
        ]);
    }

    public function store(LearningStepRequest $request): RedirectResponse
    {
        $this->authorize('create', LearningStep::class);
        $step = DB::transaction(function () use ($request): LearningStep {
            $step = LearningStep::create([
                ...$request->safe()->except(['sources', 'audiences']),
                'reviewed_at' => now(),
                'created_by' => $request->user()->id,
            ]);
            SyncLearningAudiences::execute($step, $request->validated('audiences'));

            return $step;
        });

        return to_route('learningSteps.show', $step)->with('success', $this->entityMessage('created', 'learningStep'));
    }

    public function update(LearningStepRequest $request, LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('update', $learningStep);
        DB::transaction(function () use ($request, $learningStep): void {
            if (! $learningStep->is_system && $request->validated('kind') !== LearningStepKind::Action->value) {
                $learningStep->forgetTranslations('action_label', true)->forgetTranslations('action_url', true);
            }
            $learningStep->update([
                ...$request->safe()->except(['sources', 'audiences']),
                ...(! $learningStep->is_system ? ['reviewed_at' => now()] : []),
            ]);
            if (array_key_exists('audiences', $request->validated())) {
                SyncLearningAudiences::execute($learningStep, $request->validated('audiences'));
            }
        });

        return back()->with('success', $this->entityMessage('updated', 'learningStep'));
    }

    public function destroy(LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('delete', $learningStep);
        $learningStep->delete();

        return to_route('learningSteps.index')->with('success', $this->entityMessage('deleted', 'learningStep'));
    }

    private function formOptions(User $user, string $ability): array
    {
        $locale = app()->getLocale();
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return [
            'audienceTypes' => array_map(fn (LearningAudienceType $type): string => $type->value,
                array_filter(LearningAudienceType::cases(), fn (LearningAudienceType $type): bool => $type !== LearningAudienceType::Purpose)),
            'canTargetEverywhere' => $scope->isAllScope,
            'tenants' => GetTenantsForUpserts::execute("learningSteps.{$ability}.padalinys", $this->authorizer)->all(),
            'institutionTypes' => InstitutionType::query()->get(['id', 'title', 'parent_id'])->map(fn (InstitutionType $type): array => [
                'id' => $type->id, 'title' => (string) $type->getTranslation('title', $locale), 'parent_id' => $type->parent_id,
            ])->values()->all(),
            'dutyTypes' => DutyType::query()->get(['id', 'title', 'parent_id'])->map(fn (DutyType $type): array => [
                'id' => $type->id, 'title' => (string) $type->getTranslation('title', $locale), 'parent_id' => $type->parent_id,
            ])->values()->all(),
            'responsibilities' => Role::query()->exceptSuperAdmin()->get()->map(fn (Role $role): array => [
                'id' => $role->id, 'title' => $role->displayTitle($locale),
            ])->sortBy('title')->values()->all(),
        ];
    }
}
