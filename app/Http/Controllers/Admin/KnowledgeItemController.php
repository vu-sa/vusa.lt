<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\GetUserCoordinators;
use App\Actions\ResolveLearningPath;
use App\Actions\SyncLearningAudiences;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Http\Controllers\AdminController;
use App\Http\Requests\KnowledgeItemRequest;
use App\Http\Requests\SearchLearningDocumentsRequest;
use App\Http\Requests\SyncKnowledgeItemGlossaryRequest;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\LearningAudience;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeBasePresenter;
use App\Services\Learning\ResolvedLearningItem;
use App\Services\LearningAdminPresenter;
use App\Services\LearningInstitutionContactsPresenter;
use App\Services\ModelAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeItemController extends AdminController
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function index(Request $request): Response
    {
        $this->authorize('manage', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/IndexKnowledgeItem', [
            'items' => KnowledgeBasePresenter::library(app()->getLocale(), $request->user()),
            'sections' => $this->sectionOptions($request->user(), $this->authorizer->scope($request->user(), 'learningSteps.update.padalinys')->granted ? 'update' : 'create'),
            'audienceTypes' => array_map(fn (LearningAudienceType $type): string => $type->value, LearningAudienceType::cases()),
            'sectionScopes' => collect(['create', 'update'])->mapWithKeys(fn (string $ability): array => [$ability => [
                'canTargetEverywhere' => $this->authorizer->scope($request->user(), "learningSteps.{$ability}.padalinys")->isAllScope,
                'tenants' => GetTenantsForUpserts::execute("learningSteps.{$ability}.padalinys", $this->authorizer)->all(),
            ]])->all(),
            'canCreate' => $request->user()->can('create', KnowledgeItem::class),
        ]);
    }

    public function show(Request $request, KnowledgeItem $knowledgeItem): Response
    {
        $this->authorize('view', $knowledgeItem);
        $knowledgeItem->load(['sources.document.institution.tenant', 'section', 'glossaryTerms', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->loadCount(['comments' => fn ($query) => $query->notErased()]);
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->knowledge->first(fn (ResolvedLearningItem $item): bool => $item->item->id === $knowledgeItem->id) ?? new ResolvedLearningItem($knowledgeItem, importance: $knowledgeItem->audiences->first()?->importance ?? LearningImportance::Recommended, audienceLabel: $knowledgeItem->audiences->first()?->label(app()->getLocale()) ?? '');

        return Inertia::render('Admin/Knowledge/ShowKnowledgeItem', [
            'item' => KnowledgeBasePresenter::item($item, $request->user(), app()->getLocale()),
            'inPath' => $path->contains($knowledgeItem),
            'coordinators' => GetUserCoordinators::execute($request->user()),
            'institutionContacts' => LearningInstitutionContactsPresenter::forUser($request->user(), app()->getLocale()),
            'canUpdate' => $request->user()->can('update', $knowledgeItem),
            ...($request->user()->can('update', $knowledgeItem) ? ['glossaryOptions' => LearningAdminPresenter::glossaryOptions(app()->getLocale())] : []),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/CreateKnowledgeItem', $this->formOptions($request->user(), 'create'));
    }

    public function edit(Request $request, KnowledgeItem $knowledgeItem): Response
    {
        $this->authorize('update', $knowledgeItem);

        return Inertia::render('Admin/Knowledge/EditKnowledgeItem', [
            'item' => KnowledgeBasePresenter::edit($knowledgeItem->load(['sources.document', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])),
            'canDelete' => $request->user()->can('delete', $knowledgeItem),
            ...$this->formOptions($request->user(), 'update'),
        ]);
    }

    public function store(KnowledgeItemRequest $request): RedirectResponse
    {
        $this->authorize('create', KnowledgeItem::class);
        $step = DB::transaction(function () use ($request): KnowledgeItem {
            $step = KnowledgeItem::create([
                ...$request->safe()->except(['sources', 'audiences']),
                'reviewed_at' => now(),
                'created_by' => $request->user()->id,
            ]);
            SyncLearningAudiences::execute($step, $request->validated('audiences'));
            $this->syncSources($step, $request->validated('sources', []));

            return $step;
        });

        return to_route('knowledgeItems.show', $step)->with('success', $this->entityMessage('created', 'knowledgeItem'));
    }

    public function update(KnowledgeItemRequest $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->authorize('update', $knowledgeItem);
        DB::transaction(function () use ($request, $knowledgeItem): void {
            $knowledgeItem->update([
                ...$request->safe()->except(['sources', 'audiences']),
                'knowledge_section_id' => $request->validated('knowledge_section_id'), 'reviewed_at' => now(),
            ]);
            if (array_key_exists('audiences', $request->validated())) {
                SyncLearningAudiences::execute($knowledgeItem, $request->validated('audiences'));
            }
            if (array_key_exists('sources', $request->validated())) {
                $this->syncSources($knowledgeItem, $request->validated('sources'));
            }
        });

        return back()->with('success', $this->entityMessage('updated', 'knowledgeItem'));
    }

    public function destroy(KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->authorize('delete', $knowledgeItem);
        $knowledgeItem->delete();

        return to_route('knowledgeItems.index')->with('success', $this->entityMessage('deleted', 'knowledgeItem'));
    }

    public function searchDocuments(SearchLearningDocumentsRequest $request): JsonResponse
    {
        $this->authorize('create', KnowledgeItem::class);
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

    public function syncGlossary(SyncKnowledgeItemGlossaryRequest $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->authorize('update', $knowledgeItem);
        $knowledgeItem->glossaryTerms()->sync($request->validated('term_ids') ?? []);

        return back()->with('success', $this->entityMessage('updated', 'knowledgeItem'));
    }

    private function formOptions(User $user, string $ability): array
    {
        $locale = app()->getLocale();
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return [
            'audienceTypes' => array_values(array_map(fn (LearningAudienceType $type): string => $type->value,
                array_filter(LearningAudienceType::cases(), fn (LearningAudienceType $type): bool => $type !== LearningAudienceType::Purpose))),
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
            'sections' => $this->sectionOptions($user, $ability),
        ];
    }

    private function sectionOptions(User $user, string $ability): array
    {
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return KnowledgeSection::query()->with('tenant')->when(! $scope->isAllScope,
            fn ($query) => $query->whereIn('tenant_id', $scope->tenantIds()))->orderBy('order')->orderBy('id')->get()
            ->map(fn (KnowledgeSection $section): array => [
                'id' => $section->id, 'title' => $section->getTranslations('title'),
                'description' => $section->getTranslations('description'), 'order' => $section->order,
                'tenant_id' => $section->tenant_id,
                'canUpdate' => $user->can('update', $section),
                'canDelete' => $user->can('delete', $section),
                'tenant' => $section->tenant ? ['id' => $section->tenant->id, 'shortname' => $section->tenant->shortname] : null,
            ])->all();
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function syncSources(KnowledgeItem $step, array $sources): void
    {
        $step->sources()->whereNotIn('locale', array_keys($sources))->delete();
        foreach ($sources as $locale => $source) {
            $step->sources()->updateOrCreate(['locale' => $locale], ['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
        }
    }
}
