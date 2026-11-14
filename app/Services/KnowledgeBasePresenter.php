<?php

namespace App\Services;

use App\Actions\ResolveLearningPath;
use App\Actions\SyncKnowledgeLinks;
use App\Enums\DocumentStatus;
use App\Enums\KnowledgeItemKind;
use App\Enums\KnowledgeSectionKind;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeItemSource;
use App\Models\KnowledgeSection;
use App\Models\LearningAudience;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningItem;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type KnowledgePayload array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, summary: string, summaryHtml: string, description: string, audienceLabel: string, audienceNotes: list<array{label: string, description: string}>, section: array{id: string, title: string}|null, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null, private: bool, thumbnailUrl: string|null}|null, state: string, autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, importance: LearningImportance}
 * @phpstan-type SelfLearnedPayload array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, summary: string, summaryHtml: string, description: string, audienceLabel: string, audienceNotes: list<array{label: string, description: string}>, section: array{id: string, title: string}|null, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null, private: bool, thumbnailUrl: string|null}|null, state: string, autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, importance: null}
 * @phpstan-type PageSummary array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, summary: string, summaryHtml: string, url: string, thumbnailUrl: string|null}
 * @phpstan-type IndexEntry array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, summary: string, summaryHtml: string, url: string, thumbnailUrl: string|null, sectionId: string|null, countsInProgress: bool, updatedAt: string|null}
 * @phpstan-type SectionPayload array{id: string, kind: KnowledgeSectionKind, title: string, description: string, date: string|null, order: int}
 * @phpstan-type MaterialPayload array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, summary: string, summaryHtml: string, url: string, thumbnailUrl: string|null, tenant: string|null, mine: bool}
 */
class KnowledgeBasePresenter
{
    /** @return list<array{importance: LearningImportance, done: int, total: int}> */
    public static function assignedProgress(LearningPath $path): array
    {
        return array_map(function (LearningImportance $importance) use ($path): array {
            $items = $path->knowledge->filter(fn (ResolvedLearningItem $item): bool => $item->importance === $importance);

            return ['importance' => $importance, 'done' => $items->whereIn('state', ['done', 'skipped'])->count(), 'total' => $items->count()];
        }, LearningImportance::cases());
    }

    /** @return array{sections: list<SectionPayload>, items: list<KnowledgePayload>, selfLearned: list<SelfLearnedPayload>, stats: array{pathDone: int, pathTotal: int, selfLearned: int}} */
    public static function overview(LearningPath $path, string $locale): array
    {
        $payloads = [];
        $sections = [];
        foreach ($path->knowledge as $resolved) {
            /** @var KnowledgeItem $item */
            $item = $resolved->item;
            $payloads[] = self::item($resolved, $locale);
            if ($item->section !== null) {
                $section = $item->section;
                $sections[$section->id] = self::section($section, $locale);
            }
        }
        $sections = self::sortSections(array_values($sections));

        $selfLearned = [];
        $progress = LearningProgress::query()
            ->where('user_id', $path->user->id)
            ->where('status', LearningProgressStatus::Done)
            ->whereHasMorph('progressable', [KnowledgeItem::class], fn ($query) => $query
                ->active()->whereNotIn('id', $path->knowledge->pluck('item.id')->all()))
            ->with(['progressable' => fn (MorphTo $relation) => $relation
                ->morphWith([KnowledgeItem::class => ['section', 'sources.document']])
                ->constrain([KnowledgeItem::class => fn ($query) => $query->withCount(['comments' => fn ($comments) => $comments->notErased()])])])
            ->orderByDesc('completed_at')->orderByDesc('id')->get();
        foreach ($progress as $completion) {
            /** @var KnowledgeItem $item */
            $item = $completion->progressable;
            if (ResolveLearningPath::sourceUpdated($item, 'done', $completion->completed_at)) {
                continue;
            }
            $selfLearned[] = [
                ...self::item(new ResolvedLearningItem($item, state: 'done', completedAt: $completion->completed_at), $locale),
                'importance' => null,
                'skippable' => false,
            ];
        }

        return [
            'sections' => $sections, 'items' => $payloads, 'selfLearned' => $selfLearned,
            'stats' => [
                'pathDone' => $path->knowledge->whereIn('state', ['done', 'skipped'])->count(),
                'pathTotal' => $path->knowledge->count(),
                'selfLearned' => count($selfLearned),
            ],
        ];
    }

    /**
     * The Wiki home's counter: active articles that count towards the whole Wiki, plus any on the
     * reader's path, and how many of those the reader has learned.
     *
     * @return array{done: int, total: int}
     */
    public static function wikiProgress(User $user, LearningPath $path): array
    {
        $pathIds = $path->knowledge->pluck('item.id')->all();
        $counted = KnowledgeItem::query()->active()
            ->where(fn ($query) => $query->where('counts_in_progress', true)->orWhereIn('id', $pathIds))
            ->pluck('id')->all();

        return ['done' => count(array_intersect($counted, self::doneIds($user))), 'total' => count($counted)];
    }

    /** @return list<string> */
    public static function doneIds(User $user): array
    {
        return LearningProgress::query()->where('user_id', $user->id)->where('status', LearningProgressStatus::Done)
            ->whereHasMorph('progressable', [KnowledgeItem::class], fn ($query) => $query->active())
            ->with('progressable.sources.document')->get()
            ->reject(fn (LearningProgress $progress): bool => ResolveLearningPath::sourceUpdated($progress->progressable, 'done', $progress->completed_at))
            ->pluck('progressable_id')->all();
    }

    /** @return KnowledgePayload */
    public static function item(ResolvedLearningItem $resolved, string $locale): array
    {
        /** @var KnowledgeItem $item */
        $item = $resolved->item;

        return [
            'id' => $item->id, 'kind' => $item->kind, 'title' => (string) $item->getTranslation('title', $locale),
            'abbreviation' => (string) $item->getTranslation('abbreviation', $locale),
            'summary' => self::plain($item->getTranslation('summary', $locale)), 'summaryHtml' => (string) $item->getTranslation('summary', $locale),
            'description' => (string) $item->getTranslation('description', $locale),
            'audienceLabel' => $resolved->audienceLabel, 'audienceNotes' => $resolved->audienceNotes,
            'importance' => $resolved->importance,
            'section' => $item->section ? ['id' => $item->section->id, 'title' => (string) $item->section->getTranslation('title', $locale)] : null,
            'source' => self::source($item, $locale), 'state' => $resolved->state,
            'autoCompleted' => false, 'completedAt' => $resolved->completedAt?->toIso8601String(),
            'skippable' => $resolved->importance->isSkippable(), 'insightsCount' => (int) $item->comments_count,
            'reviewedAt' => $item->reviewed_at?->toDateString(),
        ];
    }

    /**
     * The record page: the item plus everything its wiki links need.
     *
     * @return array{linkedPages: array<string, PageSummary>, related: list<PageSummary>, subjects: list<array{id: string, type: string, title: string, url: string|null}>, materials: list<MaterialPayload>, structure: array{graph: string, node: string|null, links: array<string, array{title: string, url: string}>}|null}
     */
    public static function pageContext(KnowledgeItem $item, string $locale, ?User $viewer = null): array
    {
        $item->loadMissing(['outgoingLinks' => fn ($query) => $query->active()->with('sources.document'), 'incomingLinks' => fn ($query) => $query->active()->with('sources.document'), 'subjects.subject']);
        $related = $item->outgoingLinks->concat($item->incomingLinks)->unique('id')
            ->map(fn (KnowledgeItem $page): array => self::summary($page, $locale))->values()->all();
        $collator = new \Collator($locale);
        usort($related, fn (array $a, array $b): int => (int) $collator->compare($a['title'], $b['title']));

        return [
            'linkedPages' => self::linkedPages($item->getTranslation('summary', $locale)."\n".$item->getTranslation('description', $locale), $locale, $item->id),
            'related' => $related,
            'subjects' => self::subjects($item, $locale),
            'materials' => $item->kind === KnowledgeItemKind::Term ? self::materials($item, $locale, $viewer) : [],
            'structure' => $item->structure_graph !== null ? [
                'graph' => $item->structure_graph, 'node' => $item->structure_node,
                'links' => self::graphLinks($item->structure_graph, $locale),
            ] : null,
        ];
    }

    /**
     * The article each node of a graph stands for, keyed by node id, so a node can open it.
     *
     * @return array<string, array{title: string, url: string}>
     */
    public static function graphLinks(string $graph, string $locale): array
    {
        return KnowledgeItem::query()->active()->where('structure_graph', $graph)->whereNotNull('structure_node')
            ->get(['id', 'title', 'structure_graph', 'structure_node'])
            ->mapWithKeys(fn (KnowledgeItem $page): array => [
                (string) $page->structure_node => ['title' => (string) $page->getTranslation('title', $locale), 'url' => route('knowledgeItems.show', $page)],
            ])->all();
    }

    /**
     * Material about a term, the viewer's own units first: "Padalinio nuostatai" leads with your faculty's.
     *
     * @return list<MaterialPayload>
     */
    public static function materials(KnowledgeItem $term, string $locale, ?User $viewer = null): array
    {
        $mine = $viewer?->tenants()->pluck('tenants.id')->all() ?? [];
        $isMine = fn (KnowledgeItem $page): bool => $page->tenant_id !== null && in_array($page->tenant_id, $mine, true);

        return KnowledgeItem::query()->active()->where('kind', KnowledgeItemKind::Material)
            ->whereHas('subjects', fn ($query) => $query->where('subject_type', 'knowledge_item')->where('subject_id', $term->id))
            ->with(['tenant', 'section', 'sources.document'])->get()
            ->sortBy([
                fn (KnowledgeItem $a, KnowledgeItem $b): int => $isMine($b) <=> $isMine($a),
                fn (KnowledgeItem $a, KnowledgeItem $b): int => [$a->section->order ?? PHP_INT_MAX, $a->order, $a->id] <=> [$b->section->order ?? PHP_INT_MAX, $b->order, $b->id],
            ])
            ->map(fn (KnowledgeItem $page): array => [...self::summary($page, $locale), 'tenant' => $page->tenant?->shortname, 'mine' => $isMine($page)])
            ->values()->all();
    }

    /** @return SectionPayload */
    public static function section(KnowledgeSection $section, string $locale): array
    {
        return [
            'id' => $section->id, 'kind' => $section->kind, 'title' => (string) $section->getTranslation('title', $locale),
            'description' => (string) $section->getTranslation('description', $locale),
            'date' => $section->date?->toDateString(), 'order' => $section->order,
        ];
    }

    /**
     * Topics in their order, then collections newest first, then undated ones in their order.
     *
     * @param  list<SectionPayload>  $sections
     * @return list<SectionPayload>
     */
    public static function sortSections(array $sections): array
    {
        $rank = fn (array $section): int => match (true) {
            $section['kind'] === KnowledgeSectionKind::Topic => 0,
            $section['date'] !== null => 1,
            default => 2,
        };
        usort($sections, fn (array $a, array $b): int => ($rank($a) <=> $rank($b))
            ?: (($b['date'] ?? '') <=> ($a['date'] ?? ''))
            ?: ([$a['order'], $a['id']] <=> [$b['order'], $b['id']]));

        return $sections;
    }

    /** @return list<SectionPayload> */
    public static function sections(string $locale): array
    {
        return self::sortSections(KnowledgeSection::query()->get()->map(fn (KnowledgeSection $section): array => self::section($section, $locale))->values()->all());
    }

    /**
     * A section page: its articles in their order.
     *
     * @return array{section: SectionPayload, pages: list<PageSummary>}
     */
    public static function sectionPage(KnowledgeSection $section, string $locale): array
    {
        return [
            'section' => self::section($section, $locale),
            'pages' => $section->items()->active()->with('sources.document')->get()->map(fn (KnowledgeItem $page): array => self::summary($page, $locale))->values()->all(),
        ];
    }

    /** The source's first page or page preview, only when the caller eager-loaded `sources.document`. */
    public static function thumbnail(KnowledgeItem $page, string $locale): ?string
    {
        if (! $page->relationLoaded('sources')) {
            return null;
        }
        $source = $page->sourceFor($locale);

        return $source === null ? null : self::sourceThumbnail($page, $source);
    }

    /** A document source's SharePoint preview, or a URL source's own, versioned so a new one busts the cache. */
    private static function sourceThumbnail(KnowledgeItem $page, KnowledgeItemSource $source): ?string
    {
        [$path, $version] = $source->document !== null
            ? [$source->document->thumbnail_path, $source->document->thumbnail_etag]
            : [$source->isUrl() ? $source->thumbnail_path : null, $source->thumbnail_version];

        return $path === null ? null : route('knowledgeItems.thumbnail', [$page, 'v' => substr(md5((string) $version), 0, 8)]);
    }

    /** The summary as plain text, for search, rows and meta. */
    public static function plain(mixed $html): string
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));
    }

    /** @return array<string, PageSummary> */
    public static function linkedPages(?string $html, string $locale, ?string $except = null): array
    {
        $ids = array_values(array_diff(SyncKnowledgeLinks::idsIn($html), [$except]));
        if ($ids === []) {
            return [];
        }

        return KnowledgeItem::query()->active()->whereKey($ids)->with('sources.document')->get()
            ->mapWithKeys(fn (KnowledgeItem $page): array => [$page->id => self::summary($page, $locale)])->all();
    }

    /** @return PageSummary */
    public static function summary(KnowledgeItem $page, string $locale): array
    {
        return [
            'id' => $page->id, 'kind' => $page->kind, 'title' => (string) $page->getTranslation('title', $locale),
            'abbreviation' => (string) $page->getTranslation('abbreviation', $locale),
            'summary' => self::plain($page->getTranslation('summary', $locale)),
            'summaryHtml' => HtmlSanitizerService::withoutLinks((string) $page->getTranslation('summary', $locale)),
            'url' => route('knowledgeItems.show', $page),
            'thumbnailUrl' => self::thumbnail($page, $locale),
        ];
    }

    /** @return list<IndexEntry> active pages alphabetically: every kind for the hub search and link picker, terms only for A–Ž */
    public static function index(string $locale, bool $terms = false): array
    {
        $entries = KnowledgeItem::query()->active()->when($terms, fn ($query) => $query->terms())->with('sources.document')->get()->map(fn (KnowledgeItem $page): array => [
            ...self::summary($page, $locale),
            'sectionId' => $page->knowledge_section_id,
            'countsInProgress' => $page->counts_in_progress,
            'updatedAt' => ($page->reviewed_at ?? $page->updated_at)?->toIso8601String(),
        ])->all();
        $collator = new \Collator($locale);
        usort($entries, fn (array $a, array $b): int => (int) $collator->compare($a['title'], $b['title']));

        return $entries;
    }

    /**
     * Active terms and the `[[` links between them, undirected, for the term map.
     *
     * @return array{nodes: list<array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, summary: string, summaryHtml: string, url: string, thumbnailUrl: string|null, sectionId: string|null, countsInProgress: bool, updatedAt: string|null, degree: int}>, edges: list<array{source: string, target: string}>, sections: list<SectionPayload>}
     */
    public static function graph(string $locale): array
    {
        $nodes = collect(self::index($locale, terms: true))->keyBy('id');
        $edges = DB::table('knowledge_links')
            ->whereIn('from_item_id', $nodes->keys())->whereIn('to_item_id', $nodes->keys())
            ->get(['from_item_id', 'to_item_id'])
            ->map(fn (object $link): array => [min($link->from_item_id, $link->to_item_id), max($link->from_item_id, $link->to_item_id)])
            ->unique(fn (array $pair): string => implode(':', $pair))
            ->map(fn (array $pair): array => ['source' => $pair[0], 'target' => $pair[1]])
            ->values();
        $degrees = $edges->flatMap(fn (array $edge): array => [$edge['source'], $edge['target']])->countBy();
        $sectionIds = $nodes->pluck('sectionId')->filter()->unique()->values();

        return [
            'nodes' => $nodes->map(fn (array $node): array => [...$node, 'degree' => (int) $degrees->get($node['id'], 0)])->values()->all(),
            'edges' => $edges->all(),
            'sections' => self::sortSections(KnowledgeSection::query()->whereKey($sectionIds)->get()
                ->map(fn (KnowledgeSection $section): array => self::section($section, $locale))->values()->all()),
        ];
    }

    /** @return list<array{id: string, type: string, title: string, url: string|null}> */
    public static function subjects(KnowledgeItem $item, string $locale): array
    {
        $result = [];
        foreach ($item->loadMissing('subjects.subject')->subjects as $subject) {
            $model = $subject->subject;
            if ($model === null) {
                continue;
            }
            $result[] = [
                'id' => (string) $model->getKey(), 'type' => $subject->subject_type,
                'title' => $model instanceof Institution ? (string) $model->getTranslation('name', $locale) : (string) $model->getTranslation('title', $locale),
                'url' => match (true) {
                    $model instanceof Institution => route('institutions.show', $model),
                    $model instanceof KnowledgeItem => route('knowledgeItems.show', $model),
                    $model instanceof InstitutionType => route('institutionTypes.show', $model),
                    default => route('dutyTypes.show', $model),
                },
            ];
        }

        return $result;
    }

    /**
     * Pages about an institution, directly or through one of its types.
     *
     * @return list<PageSummary>
     */
    public static function forInstitution(Institution $institution, string $locale): array
    {
        $typeIds = $institution->types()->pluck('institution_types.id')->map(fn ($id): string => (string) $id)->all();

        return KnowledgeItem::query()->active()
            ->whereHas('subjects', fn ($query) => $query
                ->where(fn ($q) => $q->where('subject_type', 'institution')->where('subject_id', (string) $institution->id))
                ->orWhere(fn ($q) => $q->where('subject_type', 'institution_type')->whereIn('subject_id', $typeIds)))
            ->ordered()->with('sources.document')->get()->map(fn (KnowledgeItem $page): array => self::summary($page, $locale))->values()->all();
    }

    /** @return array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null, private: bool, thumbnailUrl: string|null}|null */
    public static function source(KnowledgeItem $step, string $locale): ?array
    {
        $source = $step->sourceFor($locale);
        if ($source === null) {
            return null;
        }
        $document = $source->document;
        if ($document !== null) {
            $isPublic = $document->isPublished() && filled($document->anonymous_url);

            return [
                'type' => 'document', 'url' => $isPublic ? route('documents.show', $document->id) : route('knowledgeItems.openSource', $step),
                'documentId' => $document->id, 'documentStatus' => $document->status, 'title' => $document->title, 'private' => ! $isPublic,
                'thumbnailUrl' => self::sourceThumbnail($step, $source),
            ];
        }
        if ($source->url !== null) {
            return ['type' => 'url', 'url' => $source->url, 'documentId' => null, 'documentStatus' => null, 'title' => null, 'private' => false, 'thumbnailUrl' => self::sourceThumbnail($step, $source)];
        }

        return null;
    }

    /** @return list<array{id: string, kind: KnowledgeItemKind, title: string, abbreviation: string, hasSource: bool, linkBroken: bool, audiences: list<array{id: string, type: LearningAudienceType, label: string, importance: LearningImportance, stage: LearningStage|null}>, audienceTypes: list<LearningAudienceType>, section: array{id: string, title: string}|null, order: int, isActive: bool, canUpdate: bool, flags: list<'expired_document'|'updated_document'|'broken_link'>}> */
    public static function library(string $locale, ?User $viewer = null): array
    {
        $user = $viewer ?? auth()->user();
        $result = [];
        foreach (KnowledgeItem::query()->ordered()->with(['sources.document', 'section', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->get() as $step) {
            $canUpdate = $user instanceof User && $user->can('update', $step);
            $result[] = [
                'id' => $step->id,
                'kind' => $step->kind,
                'title' => (string) $step->getTranslation('title', $locale),
                'abbreviation' => (string) $step->getTranslation('abbreviation', $locale),
                'hasSource' => $step->sources->contains(fn ($source): bool => $source->document_id !== null || filled($source->url)),
                'linkBroken' => $step->sources->contains(fn ($source): bool => $source->isBroken()),
                'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                    'id' => $audience->id, 'type' => $audience->type, 'label' => $audience->label($locale),
                    'importance' => $audience->importance, 'stage' => $audience->stage,
                ])->all(),
                'audienceTypes' => $step->audiences->pluck('type')->unique()->values()->all(),
                'section' => $step->section ? ['id' => $step->section->id, 'title' => (string) $step->section->getTranslation('title', $locale)] : null,

                'order' => $step->order,
                'isActive' => $step->is_active,
                'canUpdate' => $canUpdate,
                'flags' => ($user === null || $canUpdate) ? self::flags($step) : [],
            ];
        }

        return $result;
    }

    /** @return array{id: string, kind: KnowledgeItemKind, title: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, summary: array{lt: string, en: string}, description: array{lt: string, en: string}, knowledge_section_id: string|null, tenant_id: int|null, structure_graph: string|null, structure_node: string|null, audiences: list<array{id: string, type: LearningAudienceType, source_id: string|null, tenant_id: int|null, importance: LearningImportance, stage: LearningStage|null, description: array{lt: string, en: string}, order: int, label: string}>, is_active: bool, counts_in_progress: bool, sources: array<string, array{document_id: int|null, url: string|null}>, source_documents: array<string, array{id: int, title: string, status: string, date: string|null}>} */
    public static function edit(KnowledgeItem $step): array
    {
        $sources = [];
        $sourceDocuments = [];
        foreach ($step->sources as $source) {
            $sources[$source->locale] = ['document_id' => $source->document_id, 'url' => $source->url];
            if ($source->document !== null) {
                $sourceDocuments[$source->locale] = [
                    'id' => $source->document->id,
                    'title' => (string) $source->document->title,
                    'status' => $source->document->status->value,
                    'date' => $source->document->document_date?->toDateString(),
                ];
            }
        }

        return [
            'id' => $step->id,
            'kind' => $step->kind,
            'title' => ['lt' => (string) $step->getTranslation('title', 'lt', false), 'en' => (string) $step->getTranslation('title', 'en', false)],
            'abbreviation' => ['lt' => (string) $step->getTranslation('abbreviation', 'lt', false), 'en' => (string) $step->getTranslation('abbreviation', 'en', false)],
            'summary' => ['lt' => (string) $step->getTranslation('summary', 'lt', false), 'en' => (string) $step->getTranslation('summary', 'en', false)],
            'description' => ['lt' => (string) $step->getTranslation('description', 'lt', false), 'en' => (string) $step->getTranslation('description', 'en', false)],
            'knowledge_section_id' => $step->knowledge_section_id, 'tenant_id' => $step->tenant_id,
            'structure_graph' => $step->structure_graph, 'structure_node' => $step->structure_node,
            'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                'id' => $audience->id, 'type' => $audience->type, 'source_id' => $audience->source_id,
                'tenant_id' => $audience->tenant_id, 'importance' => $audience->importance, 'stage' => $audience->stage,
                'description' => ['lt' => (string) $audience->getTranslation('description', 'lt', false), 'en' => (string) $audience->getTranslation('description', 'en', false)],
                'order' => $audience->order, 'label' => $audience->label(app()->getLocale()),
            ])->all(),
            'is_active' => $step->is_active,
            'counts_in_progress' => $step->counts_in_progress,
            'sources' => $sources, 'source_documents' => $sourceDocuments,
        ];
    }

    /** @return list<'expired_document'|'updated_document'|'broken_link'> */
    private static function flags(KnowledgeItem $step): array
    {
        $flags = [];
        if ($step->sources->contains(fn ($source): bool => $source->isBroken())) {
            $flags[] = 'broken_link';
        }
        $baseline = $step->reviewed_at ?? $step->updated_at;
        $documents = $step->sources->map(fn ($source) => $source->document)->filter();

        if ($documents->contains(fn ($document): bool => $document->is_in_effect === false)) {
            $flags[] = 'expired_document';
        }
        if ($documents->contains(fn ($document): bool => $document->sharepoint_modified_at !== null && ($baseline === null || $document->sharepoint_modified_at->gt($baseline)))) {
            $flags[] = 'updated_document';
        }

        return $flags;
    }
}
