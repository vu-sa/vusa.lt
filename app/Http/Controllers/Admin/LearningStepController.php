<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\GetUserCoordinators;
use App\Actions\ResolveLearningPath;
use App\Enums\LearningLayer;
use App\Http\Controllers\AdminController;
use App\Http\Requests\LearningStepRequest;
use App\Http\Requests\SearchLearningDocumentsRequest;
use App\Http\Requests\SyncLearningStepGlossaryRequest;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\User;
use App\Services\Learning\ResolvedLearningStep;
use App\Services\LearningAdminPresenter;
use App\Services\LearningInstitutionContactsPresenter;
use App\Services\LearningPathPresenter;
use App\Services\ModelAuthorizer;
use Illuminate\Http\JsonResponse;
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
        $this->authorize('viewAny', LearningStep::class);

        return Inertia::render('Admin/Learning/IndexLearningStep', [
            'steps' => LearningPathPresenter::library(app()->getLocale(), $request->user()),
            'canCreate' => $request->user()->can('create', LearningStep::class),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function show(Request $request, LearningStep $learningStep): Response
    {
        $this->authorize('view', $learningStep);
        $learningStep->load(['sources.document.institution.tenant', 'institutionType', 'dutyType', 'tenant', 'glossaryTerms'])->loadCount('comments');
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->steps->concat($path->meetingPrep)->first(fn (ResolvedLearningStep $item): bool => $item->step->id === $learningStep->id) ?? new ResolvedLearningStep($learningStep);

        return Inertia::render('Admin/Learning/ShowLearningStep', [
            'step' => LearningPathPresenter::step($item, $request->user(), app()->getLocale()),
            'inPath' => $path->contains($learningStep->id),
            'coordinators' => GetUserCoordinators::execute($request->user()),
            'institutionContacts' => LearningInstitutionContactsPresenter::forUser($request->user(), app()->getLocale()),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
            'canUpdate' => $request->user()->can('update', $learningStep),
            'glossaryOptions' => $request->user()->can('update', $learningStep) ? LearningAdminPresenter::glossaryOptions(app()->getLocale()) : [],
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
            'step' => LearningPathPresenter::edit($learningStep->load('sources.document')),
            'canDelete' => $request->user()->can('delete', $learningStep),
            ...$this->formOptions($request->user(), 'update'),
        ]);
    }

    public function store(LearningStepRequest $request): RedirectResponse
    {
        $this->authorize('create', LearningStep::class);
        $step = DB::transaction(function () use ($request): LearningStep {
            $step = LearningStep::create([
                ...$request->safe()->except('sources'),
                'reviewed_at' => now(),
                'created_by' => $request->user()->id,
            ]);
            $this->syncSources($step, $request->validated('sources', []));

            return $step;
        });

        return to_route('learningSteps.show', $step)->with('success', $this->entityMessage('created', 'learningStep'));
    }

    public function update(LearningStepRequest $request, LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('update', $learningStep);
        DB::transaction(function () use ($request, $learningStep): void {
            $learningStep->update([
                ...$request->safe()->except('sources'),
                'reviewed_at' => now(),
            ]);
            if (! $learningStep->is_system && array_key_exists('sources', $request->validated())) {
                $this->syncSources($learningStep, $request->validated('sources'));
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

    public function searchDocuments(SearchLearningDocumentsRequest $request): JsonResponse
    {
        $this->authorize('create', LearningStep::class);
        $query = trim((string) ($request->validated('q') ?? ''));

        $documents = Document::query()
            ->browsableBy($request->user())
            ->when($query !== '', fn ($builder) => $builder->where('title', 'like', "%{$query}%"))
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'title', 'status', 'document_date']);

        return response()->json($documents->map(fn (Document $document): array => [
            'id' => $document->id,
            'title' => (string) $document->title,
            'status' => $document->status->value,
            'date' => $document->document_date?->toDateString(),
        ])->values()->all());
    }

    public function syncGlossary(SyncLearningStepGlossaryRequest $request, LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('update', $learningStep);
        $learningStep->glossaryTerms()->sync($request->validated('term_ids') ?? []);

        return back()->with('success', $this->entityMessage('updated', 'learningStep'));
    }

    /** @return array{layers: list<string>, tenants: list<array{id: int, shortname: string, fullname: string, type: string|null}>, institutionTypes: list<array{id: int, title: string, parent_id: int|null}>, dutyTypes: list<array{id: int, title: string, parent_id: int|null}>, canSeeReps: bool} */
    private function formOptions(User $user, string $ability): array
    {
        $locale = app()->getLocale();
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");
        $layers = $scope->isAllScope
            ? array_map(fn (LearningLayer $layer): string => $layer->value, LearningLayer::cases())
            : ($scope->granted ? [LearningLayer::Tenant->value] : []);

        return [
            'layers' => $layers,
            'tenants' => GetTenantsForUpserts::execute("learningSteps.{$ability}.padalinys", $this->authorizer)->all(),
            'institutionTypes' => $scope->isAllScope
                ? InstitutionType::query()->get(['id', 'title', 'parent_id'])->map(fn (InstitutionType $type): array => [
                    'id' => $type->id,
                    'title' => (string) $type->getTranslation('title', $locale),
                    'parent_id' => $type->parent_id,
                ])->values()->all()
                : [],
            'dutyTypes' => $scope->isAllScope
                ? DutyType::query()->get(['id', 'title', 'parent_id'])->map(fn (DutyType $type): array => [
                    'id' => $type->id,
                    'title' => (string) $type->getTranslation('title', $locale),
                    'parent_id' => $type->parent_id,
                ])->values()->all()
                : [],
            'canSeeReps' => LearningAdminPresenter::canSeeReps($user),
        ];
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function syncSources(LearningStep $step, array $sources): void
    {
        $step->sources()->whereNotIn('locale', array_keys($sources))->delete();
        foreach ($sources as $locale => $source) {
            $step->sources()->updateOrCreate(['locale' => $locale], ['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
        }
    }
}
