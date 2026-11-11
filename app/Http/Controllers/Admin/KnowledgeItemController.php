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
use App\Jobs\RefreshDocumentThumbnailJob;
use App\Models\Document;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\LearningAudience;
use App\Models\Role;
use App\Models\User;
use App\Policies\KnowledgeItemPolicy;
use App\Services\Authorization\PermissionScope;
use App\Services\Documents\DocumentPreviewService;
use App\Services\KnowledgeBasePresenter;
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
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function index(Request $request): Response
    {
        $this->authorize('manage', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/IndexKnowledgeItem', [
            'items' => KnowledgeBasePresenter::library(app()->getLocale(), $request->user()),
            'sections' => $this->sectionOptions($request->user(), $this->sectionScope($request->user(), 'update')->granted ? 'update' : 'create'),
            'audienceTypes' => array_map(fn (LearningAudienceType $type): string => $type->value, LearningAudienceType::cases()),
            'sectionScopes' => collect(['create', 'update'])->mapWithKeys(fn (string $ability): array => [$ability => [
                'canTargetEverywhere' => $this->sectionScope($request->user(), $ability)->isAllScope,
                'tenants' => GetTenantsForUpserts::execute($this->sectionPermission($request->user(), $ability), $this->authorizer)->all(),
            ]])->all(),
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
                state: $progress === null ? 'pending' : 'done',
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
            ...KnowledgeBasePresenter::pageContext($knowledgeItem, app()->getLocale()),
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
        $document = $this->sourceDocument($knowledgeItem);
        abort_if($document?->thumbnail_path === null || ! Storage::exists($document->thumbnail_path), 404);

        return response()->file(Storage::path($document->thumbnail_path), ['Content-Type' => 'image/webp', 'Cache-Control' => 'private, max-age=604800']);
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
            'item' => KnowledgeBasePresenter::edit($knowledgeItem->load(['sources.document', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])),
            'canDelete' => $request->user()->can('delete', $knowledgeItem),
            ...$this->formOptions($request->user(), 'update', $knowledgeItem),
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
            SyncLearningAudiences::execute($step, $request->validated('audiences', []));
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
                'knowledge_section_id' => $request->validated('knowledge_section_id'), 'tenant_id' => $request->validated('tenant_id'), 'reviewed_at' => now(),
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

    /** Every active page, for the editor's `[[` link picker. */
    public function linkOptions(): JsonResponse
    {
        $this->authorize('manage', KnowledgeItem::class);

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

    /** @return array{institutionTypes: list<array{id: int, title: string, parent_id: int|null}>, dutyTypes: list<array{id: int, title: string, parent_id: int|null}>} */
    private function subjectOptions(): array
    {
        $locale = app()->getLocale();
        $options = fn ($types): array => $types->map(fn ($type): array => ['id' => $type->id, 'title' => (string) $type->getTranslation('title', $locale), 'parent_id' => $type->parent_id])
            ->values()->all();

        return ['institutionTypes' => $options(InstitutionType::query()->get(['id', 'title', 'parent_id'])), 'dutyTypes' => $options(DutyType::query()->get(['id', 'title', 'parent_id']))];
    }

    private function formOptions(User $user, string $ability, ?KnowledgeItem $item = null): array
    {
        $locale = app()->getLocale();
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");
        $policy = app(KnowledgeItemPolicy::class);
        $knowledgeScope = $this->authorizer->scope($user, "knowledgeItems.{$ability}.padalinys");

        return [
            'canManageAudiences' => $policy->canManageAudiences($user, $ability) && ($item === null || $policy->manageAudiences($user, $item->audiences, $ability)),
            'pageTenants' => GetTenantsForUpserts::execute(($knowledgeScope->granted ? 'knowledgeItems' : 'learningSteps').".{$ability}.padalinys", $this->authorizer)->all(),
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
            'sections' => $this->sectionOptions($user, $ability),
        ];
    }

    /** Sections belong to the wiki, so its editors manage them; path builders keep the access they had. */
    private function sectionPermission(User $user, string $ability): string
    {
        $knowledge = $this->authorizer->scope($user, "knowledgeItems.{$ability}.padalinys");
        $learning = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return ($knowledge->isAllScope || ($knowledge->granted && ! $learning->isAllScope) ? 'knowledgeItems' : 'learningSteps').".{$ability}.padalinys";
    }

    private function sectionScope(User $user, string $ability): PermissionScope
    {
        return $this->authorizer->scope($user, $this->sectionPermission($user, $ability));
    }

    private function sectionOptions(User $user, string $ability): array
    {
        $scope = $this->sectionScope($user, $ability);

        return KnowledgeSection::query()->with('tenant')->withCount('items')->when(! $scope->isAllScope,
            fn ($query) => $query->whereIn('tenant_id', $scope->tenantIds()))->orderBy('order')->orderBy('id')->get()
            ->map(fn (KnowledgeSection $section): array => [
                'id' => $section->id, 'title' => $section->getTranslations('title'),
                'description' => $section->getTranslations('description'), 'order' => $section->order,
                'tenant_id' => $section->tenant_id,
                'itemsCount' => $section->items_count,
                'canUpdate' => $user->can('update', $section),
                'canDelete' => $user->can('delete', $section),
                'tenant' => $section->tenant ? ['id' => $section->tenant->id, 'shortname' => $section->tenant->shortname] : null,
            ])->all();
    }

    private function sourceDocument(KnowledgeItem $knowledgeItem): ?Document
    {
        return $knowledgeItem->load('sources.document')->sourceFor(app()->getLocale())?->document;
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function syncSources(KnowledgeItem $step, array $sources): void
    {
        $step->sources()->whereNotIn('locale', array_keys($sources))->delete();
        foreach ($sources as $locale => $source) {
            $step->sources()->updateOrCreate(['locale' => $locale], ['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
        }

        $previews = app(DocumentPreviewService::class);
        Document::query()->whereKey(array_filter(array_column($sources, 'document_id')))->get()
            ->filter(fn (Document $document): bool => $previews->needsThumbnail($document))
            ->each(fn (Document $document) => RefreshDocumentThumbnailJob::dispatch($document)->afterCommit());
    }
}
