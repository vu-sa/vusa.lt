<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Actions\GetUserCoordinators;
use App\Actions\ResolveLearningPath;
use App\Actions\SyncLearningAudiences;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\KnowledgeItemRequest;
use App\Http\Requests\SearchLearningDocumentsRequest;
use App\Http\Requests\SyncKnowledgeItemSubjectsRequest;
use App\Jobs\CheckKnowledgeSourceUrlJob;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningAudience;
use App\Models\Role;
use App\Models\User;
use App\Policies\KnowledgeItemPolicy;
use App\Services\Documents\DocumentPreviewService;
use App\Services\KnowledgeBasePresenter;
use App\Services\KnowledgeSectionOptions;
use App\Services\Learning\ResolvedLearningItem;
use App\Services\LearningInstitutionContactsPresenter;
use App\Services\ModelAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KnowledgeItemController extends AdminController
{
    public function __construct(private readonly ModelAuthorizer $authorizer, private readonly KnowledgeSectionOptions $sections) {}

    public function index(Request $request): Response
    {
        $this->authorize('manage', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/IndexKnowledgeItem', [
            'items' => KnowledgeBasePresenter::library(app()->getLocale(), $request->user()),
            'sections' => $this->sections->options($request->user(), $this->sections->scope($request->user(), 'update')->granted ? 'update' : 'create'),
            'audienceTypes' => array_map(fn (LearningAudienceType $type): string => $type->value, LearningAudienceType::cases()),
            'sectionScopes' => $this->sections->scopes($request->user()),
            'canCreate' => $request->user()->can('create', KnowledgeItem::class),
        ]);
    }

    public function show(Request $request, KnowledgeItem $knowledgeItem): Response
    {
        $this->authorize('view', $knowledgeItem);
        $knowledgeItem->load(['sources.document.institution.tenant', 'section', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->loadCount(['comments' => fn ($query) => $query->notErased()]);
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->knowledge->first(fn (ResolvedLearningItem $item): bool => $item->item->id === $knowledgeItem->id);
        $inPath = $item !== null;
        if ($item === null) {
            $progress = $knowledgeItem->progress()->where('user_id', $request->user()->id)->where('status', LearningProgressStatus::Done)->first();
            $item = new ResolvedLearningItem(
                $knowledgeItem,
                state: $progress === null ? 'pending' : (ResolveLearningPath::sourceUpdated($knowledgeItem, 'done', $progress->completed_at) ? 'updated' : 'done'),
                completedAt: $progress?->completed_at,
                importance: $knowledgeItem->audiences->first()->importance ?? LearningImportance::Recommended,
                audienceLabel: $knowledgeItem->audiences->first()?->label(app()->getLocale()) ?? '',
            );
        }
        $payload = KnowledgeBasePresenter::item($item, app()->getLocale());
        if (! $inPath) {
            $payload['skippable'] = false;
        }

        $canUpdate = $request->user()->can('update', $knowledgeItem);

        return Inertia::render('Admin/Knowledge/ShowKnowledgeItem', [
            'item' => $payload,
            ...KnowledgeBasePresenter::pageContext($knowledgeItem, app()->getLocale(), $request->user()),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
            'inPath' => $inPath,
            'canTrack' => $knowledgeItem->is_active && $request->user()->can('view', $knowledgeItem),
            'coordinators' => GetUserCoordinators::execute($request->user()),
            'institutionContacts' => LearningInstitutionContactsPresenter::forUser($request->user(), app()->getLocale()),
            'canUpdate' => $canUpdate,
            ...($canUpdate ? ['subjectOptions' => $this->subjectOptions()] : []),
        ]);
    }

    public function thumbnail(KnowledgeItem $knowledgeItem): BinaryFileResponse
    {
        $this->authorize('view', $knowledgeItem);
        $source = $knowledgeItem->load('sources.document')->sourceFor(app()->getLocale());
        $path = $source?->document !== null ? $source->document->thumbnail_path : ($source?->isUrl() ? $source->thumbnail_path : null);
        abort_if($path === null || ! Storage::exists($path), 404);

        // BinaryFileResponse makes itself public by default; a hidden document's preview must not land in shared caches.
        return response()->file(Storage::path($path), ['Content-Type' => 'image/webp'])->setPrivate()->setMaxAge(604800);
    }

    /**
     * Readers of the item may open its document even when it is hidden from the public site.
     */
    public function openSource(KnowledgeItem $knowledgeItem, DocumentPreviewService $previews): RedirectResponse
    {
        $this->authorize('view', $knowledgeItem);
        $document = $this->sourceDocument($knowledgeItem);
        abort_if($document === null, 404);

        if ($document->isPublished() && filled($document->anonymous_url)) {
            return redirect()->route('documents.show', $document->id);
        }

        $url = $previews->temporaryUrl($document);

        return $url === null
            ? back()->with('error', __('learning.path.document_open_failed'))
            : redirect()->away($url);
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
            'item' => KnowledgeBasePresenter::edit($knowledgeItem->load(['sources.document', 'structureNodes', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])),
            'canDelete' => $request->user()->can('delete', $knowledgeItem),
            ...$this->formOptions($request->user(), 'update', $knowledgeItem),
        ]);
    }

    public function store(KnowledgeItemRequest $request): RedirectResponse
    {
        $this->authorize('create', KnowledgeItem::class);
        $step = DB::transaction(function () use ($request): KnowledgeItem {
            $step = KnowledgeItem::create([
                ...$request->safe()->except(['sources', 'audiences', 'structure']),
                'order' => $this->nextOrder($request->validated('knowledge_section_id')),
                'reviewed_at' => now(),
                'created_by' => $request->user()->id,
            ]);
            SyncLearningAudiences::execute($step, $request->validated('audiences', []));
            $this->syncSources($step, $request->validated('sources', []));
            $this->syncStructure($step, $request->validated('structure', []));

            return $step;
        });

        return to_route('knowledgeItems.show', $step)->with('success', $this->entityMessage('created', 'knowledgeItem'));
    }

    public function update(KnowledgeItemRequest $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->authorize('update', $knowledgeItem);
        DB::transaction(function () use ($request, $knowledgeItem): void {
            $sectionId = $request->validated('knowledge_section_id');
            $knowledgeItem->update([
                ...$request->safe()->except(['sources', 'audiences', 'structure']),
                ...($sectionId !== $knowledgeItem->knowledge_section_id ? ['order' => $this->nextOrder($sectionId)] : []),
                'knowledge_section_id' => $sectionId, 'tenant_id' => $request->validated('tenant_id'), 'reviewed_at' => now(),
            ]);
            if (array_key_exists('audiences', $request->validated())) {
                SyncLearningAudiences::execute($knowledgeItem, $request->validated('audiences'));
            }
            if (array_key_exists('sources', $request->validated())) {
                $this->syncSources($knowledgeItem, $request->validated('sources'));
            }
            if (array_key_exists('structure', $request->validated())) {
                $this->syncStructure($knowledgeItem, $request->validated('structure'));
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
        $this->authorize('manage', KnowledgeItem::class);
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

    /** Every active page, for the editor's `[[` picker and the command palette; readers already get it on the Wiki home. */
    public function linkOptions(): JsonResponse
    {
        $this->authorize('viewAny', KnowledgeItem::class);

        return response()->json(KnowledgeBasePresenter::index(app()->getLocale()));
    }

    public function syncSubjects(SyncKnowledgeItemSubjectsRequest $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->authorize('update', $knowledgeItem);
        DB::transaction(function () use ($request, $knowledgeItem): void {
            $knowledgeItem->subjects()->delete();
            foreach (collect($request->validated('subjects'))->unique(fn (array $subject): string => $subject['type'].':'.$subject['id']) as $subject) {
                $knowledgeItem->subjects()->create(['subject_type' => $subject['type'], 'subject_id' => (string) $subject['id']]);
            }
        });

        return back()->with('success', $this->entityMessage('updated', 'knowledgeItem'));
    }

    /** @return array{institutionTypes: list<array{id: int, title: string, parent_id: int|null}>, dutyTypes: list<array{id: int, title: string, parent_id: int|null}>, terms: list<array{id: string, title: string}>} */
    private function subjectOptions(): array
    {
        $locale = app()->getLocale();
        $options = fn ($types): array => $types->map(fn ($type): array => ['id' => $type->id, 'title' => (string) $type->getTranslation('title', $locale), 'parent_id' => $type->parent_id])
            ->values()->all();

        $terms = [];
        foreach (KnowledgeItem::query()->active()->terms()->get(['id', 'title']) as $term) {
            $terms[] = ['id' => $term->id, 'title' => (string) $term->getTranslation('title', $locale)];
        }
        $collator = new \Collator($locale);
        usort($terms, fn (array $a, array $b): int => (int) $collator->compare($a['title'], $b['title']));

        return ['institutionTypes' => $options(InstitutionType::query()->get(['id', 'title', 'parent_id'])), 'dutyTypes' => $options(DutyType::query()->get(['id', 'title', 'parent_id'])), 'terms' => $terms];
    }

    private function formOptions(User $user, string $ability, ?KnowledgeItem $item = null): array
    {
        $locale = app()->getLocale();
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");
        $policy = app(KnowledgeItemPolicy::class);
        $knowledgeScope = $this->authorizer->scope($user, "knowledgeItems.{$ability}.padalinys");

        return [
            'canManageAudiences' => $policy->canManageAudiences($user, $ability) && ($item === null || $policy->manageAudiences($user, $item->audiences, $ability)),
            'pageTenants' => GetTenantsForUpserts::execute("knowledgeItems.{$ability}.padalinys", $this->authorizer)
                ->toBase()->merge(GetTenantsForUpserts::execute("learningSteps.{$ability}.padalinys", $this->authorizer))->unique('id')->sortBy('shortname')->values()->all(),
            'canPublishEverywhere' => $knowledgeScope->isAllScope || $scope->isAllScope,
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
            'sections' => $this->sections->options($user, $ability),
        ];
    }

    /** A new or moved article goes to the end of its section; the order is set on the section page. */
    private function nextOrder(?string $sectionId): int
    {
        return (int) KnowledgeItem::query()->where('knowledge_section_id', $sectionId)->max('order') + 1;
    }

    private function sourceDocument(KnowledgeItem $knowledgeItem): ?Document
    {
        return $knowledgeItem->load('sources.document')->sourceFor(app()->getLocale())?->document;
    }

    /**
     * Replaces the article's places in the structure graphs, in the order given.
     *
     * @param  list<array{graph: string, node?: string|null}>  $structure
     */
    private function syncStructure(KnowledgeItem $item, array $structure): void
    {
        $item->structureNodes()->delete();
        foreach ($structure as $order => $place) {
            $item->structureNodes()->create(['graph' => $place['graph'], 'node' => $place['node'] ?? null, 'order' => $order]);
        }
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function syncSources(KnowledgeItem $step, array $sources): void
    {
        foreach ($step->sources()->whereNotIn('locale', array_keys($sources))->get() as $removed) {
            if ($removed->thumbnail_path !== null) {
                Storage::delete($removed->thumbnail_path);
            }
            $removed->delete();
        }
        foreach ($sources as $locale => $source) {
            $model = $step->sources()->firstOrNew(['locale' => $locale]);
            $model->fill(['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
            $changed = ! $model->exists || $model->isDirty(['url', 'document_id']);
            if ($changed) {
                if ($model->thumbnail_path !== null) {
                    Storage::delete($model->thumbnail_path);
                }
                $model->forceFill(['checked_at' => null, 'check_status' => null, 'check_failures' => 0, 'content_version' => null, 'thumbnail_path' => null, 'thumbnail_version' => null]);
            }
            $model->save();
            if ($changed && $model->isUrl()) {
                CheckKnowledgeSourceUrlJob::dispatch($model)->afterCommit();
            }
        }

        $previews = app(DocumentPreviewService::class);
        Document::query()->whereKey(array_filter(array_column($sources, 'document_id')))->get()
            ->each(fn (Document $document) => $previews->queueThumbnail($document));
    }
}
