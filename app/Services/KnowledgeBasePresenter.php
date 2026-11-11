<?php

namespace App\Services;

use App\Actions\SyncKnowledgeLinks;
use App\Enums\DocumentStatus;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningAudience;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Learning\LearningPath;
use App\Services\Learning\ResolvedLearningItem;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @phpstan-type KnowledgeContentPayload array{id: string, title: string, abbreviation: string, summary: string, description: string, audienceLabel: string, audienceNotes: list<array{label: string, description: string}>, section: array{id: string, title: string}|null, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: DocumentStatus|null, title: string|null, private: bool, thumbnailUrl: string|null}|null, state: string, autoCompleted: bool, completedAt: string|null, skippable: bool, insightsCount: int, reviewedAt: string|null, perTerm: bool}
 * @phpstan-type KnowledgePayload KnowledgeContentPayload&array{importance: LearningImportance}
 * @phpstan-type SelfLearnedPayload KnowledgeContentPayload&array{importance: null}
 * @phpstan-type PageSummary array{id: string, title: string, abbreviation: string, summary: string, url: string}
 * @phpstan-type IndexEntry array{id: string, title: string, abbreviation: string, summary: string, url: string, sectionId: string|null, updatedAt: string|null}
 */
class KnowledgeBasePresenter
{
    /** @return array{sections: list<array{id: string, title: string, description: string, order: int}>, items: list<KnowledgePayload>, selfLearned: list<SelfLearnedPayload>, stats: array{pathDone: int, pathTotal: int, selfLearned: int}} */
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
                $sections[$section->id] = ['id' => $section->id, 'title' => (string) $section->getTranslation('title', $locale),
                    'description' => (string) $section->getTranslation('description', $locale), 'order' => $section->order];
            }
        }
        $sections = array_values($sections);
        usort($sections, fn (array $a, array $b): int => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]);

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

    /** @return list<string> */
    public static function doneIds(User $user): array
    {
        return LearningProgress::query()->where('user_id', $user->id)->where('status', LearningProgressStatus::Done)
            ->whereHasMorph('progressable', [KnowledgeItem::class], fn ($query) => $query->active())
            ->pluck('progressable_id')->all();
    }

    /** @return KnowledgePayload */
    public static function item(ResolvedLearningItem $resolved, string $locale): array
    {
        /** @var KnowledgeItem $item */
        $item = $resolved->item;

        return [
            'id' => $item->id, 'title' => (string) $item->getTranslation('title', $locale),
            'abbreviation' => (string) $item->getTranslation('abbreviation', $locale), 'summary' => (string) $item->getTranslation('summary', $locale),
            'description' => (string) $item->getTranslation('description', $locale),
            'audienceLabel' => $resolved->audienceLabel, 'audienceNotes' => $resolved->audienceNotes,
            'importance' => $resolved->importance,
            'section' => $item->section ? ['id' => $item->section->id, 'title' => (string) $item->section->getTranslation('title', $locale)] : null,
            'source' => self::source($item, $locale), 'state' => $resolved->state,
            'autoCompleted' => false, 'completedAt' => $resolved->completedAt?->toIso8601String(),
            'skippable' => $resolved->importance->isSkippable(), 'insightsCount' => (int) $item->comments_count,
            'reviewedAt' => $item->reviewed_at?->toDateString(), 'perTerm' => $item->per_term,
        ];
    }

    /**
     * The record page: the item plus everything its wiki links need.
     *
     * @return array{linkedPages: array<string, PageSummary>, related: list<PageSummary>, subjects: list<array{id: string, type: string, title: string, url: string|null}>}
     */
    public static function pageContext(KnowledgeItem $item, string $locale): array
    {
        $item->loadMissing(['outgoingLinks' => fn ($query) => $query->active(), 'incomingLinks' => fn ($query) => $query->active(), 'subjects.subject']);
        $related = $item->outgoingLinks->concat($item->incomingLinks)->unique('id')
            ->map(fn (KnowledgeItem $page): array => self::summary($page, $locale))->values()->all();
        $collator = new \Collator($locale);
        usort($related, fn (array $a, array $b): int => (int) $collator->compare($a['title'], $b['title']));

        return [
            'linkedPages' => self::linkedPages($item->getTranslation('description', $locale), $locale, $item->id),
            'related' => $related,
            'subjects' => self::subjects($item, $locale),
        ];
    }

    /** @return array<string, PageSummary> */
    public static function linkedPages(?string $html, string $locale, ?string $except = null): array
    {
        $ids = array_values(array_diff(SyncKnowledgeLinks::idsIn($html), [$except]));
        if ($ids === []) {
            return [];
        }

        return KnowledgeItem::query()->active()->whereKey($ids)->get()
            ->mapWithKeys(fn (KnowledgeItem $page): array => [$page->id => self::summary($page, $locale)])->all();
    }

    /** @return PageSummary */
    public static function summary(KnowledgeItem $page, string $locale): array
    {
        return [
            'id' => $page->id, 'title' => (string) $page->getTranslation('title', $locale),
            'abbreviation' => (string) $page->getTranslation('abbreviation', $locale),
            'summary' => (string) $page->getTranslation('summary', $locale),
            'url' => route('knowledgeItems.show', $page),
        ];
    }

    /** @return list<IndexEntry> every active page, alphabetically, for the hub search, A–Ž and the editor's link picker */
    public static function index(string $locale): array
    {
        $entries = KnowledgeItem::query()->active()->get()->map(fn (KnowledgeItem $page): array => [
            ...self::summary($page, $locale),
            'sectionId' => $page->knowledge_section_id,
            'updatedAt' => ($page->reviewed_at ?? $page->updated_at)?->toIso8601String(),
        ])->all();
        $collator = new \Collator($locale);
        usort($entries, fn (array $a, array $b): int => (int) $collator->compare($a['title'], $b['title']));

        return $entries;
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
            ->ordered()->get()->map(fn (KnowledgeItem $page): array => self::summary($page, $locale))->values()->all();
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
                'thumbnailUrl' => $document->thumbnail_path !== null ? route('knowledgeItems.thumbnail', [$step, 'v' => substr(md5((string) $document->thumbnail_etag), 0, 8)]) : null,
            ];
        }
        if ($source->url !== null) {
            return ['type' => 'url', 'url' => $source->url, 'documentId' => null, 'documentStatus' => null, 'title' => null, 'private' => false, 'thumbnailUrl' => null];
        }

        return null;
    }

    /** @return list<array{id: string, title: string, abbreviation: string, hasSource: bool, audiences: list<array{id: string, type: LearningAudienceType, label: string, importance: LearningImportance, stage: LearningStage|null}>, audienceTypes: list<LearningAudienceType>, section: array{id: string, title: string}|null, order: int, isActive: bool, canUpdate: bool, perTerm: bool, flags: list<'expired_document'|'updated_document'|'missing_source'>}> */
    public static function library(string $locale, ?User $viewer = null): array
    {
        $user = $viewer ?? auth()->user();
        $result = [];
        foreach (KnowledgeItem::query()->ordered()->with(['sources.document', 'section', 'audiences' => fn ($query) => $query->with(LearningAudience::LABEL_RELATIONS)])->get() as $step) {
            $canUpdate = $user instanceof User && $user->can('update', $step);
            $result[] = [
                'id' => $step->id,
                'title' => (string) $step->getTranslation('title', $locale),
                'abbreviation' => (string) $step->getTranslation('abbreviation', $locale),
                'hasSource' => $step->sources->contains(fn ($source): bool => $source->document_id !== null || filled($source->url)),
                'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                    'id' => $audience->id, 'type' => $audience->type, 'label' => $audience->label($locale),
                    'importance' => $audience->importance, 'stage' => $audience->stage,
                ])->all(),
                'audienceTypes' => $step->audiences->pluck('type')->unique()->values()->all(),
                'section' => $step->section ? ['id' => $step->section->id, 'title' => (string) $step->section->getTranslation('title', $locale)] : null,
                'perTerm' => $step->per_term,
                'order' => $step->order,
                'isActive' => $step->is_active,
                'canUpdate' => $canUpdate,
                'flags' => ($user === null || $canUpdate) ? self::flags($step) : [],
            ];
        }

        return $result;
    }

    /** @return array{id: string, title: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, summary: array{lt: string, en: string}, description: array{lt: string, en: string}, knowledge_section_id: string|null, tenant_id: int|null, audiences: list<array{id: string, type: LearningAudienceType, source_id: string|null, tenant_id: int|null, importance: LearningImportance, stage: LearningStage|null, description: array{lt: string, en: string}, order: int, label: string}>, per_term: bool, order: int, is_active: bool, sources: array<string, array{document_id: int|null, url: string|null}>, source_documents: array<string, array{id: int, title: string, status: string, date: string|null}>} */
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
            'title' => ['lt' => (string) $step->getTranslation('title', 'lt', false), 'en' => (string) $step->getTranslation('title', 'en', false)],
            'abbreviation' => ['lt' => (string) $step->getTranslation('abbreviation', 'lt', false), 'en' => (string) $step->getTranslation('abbreviation', 'en', false)],
            'summary' => ['lt' => (string) $step->getTranslation('summary', 'lt', false), 'en' => (string) $step->getTranslation('summary', 'en', false)],
            'description' => ['lt' => (string) $step->getTranslation('description', 'lt', false), 'en' => (string) $step->getTranslation('description', 'en', false)],
            'knowledge_section_id' => $step->knowledge_section_id, 'tenant_id' => $step->tenant_id,
            'audiences' => $step->audiences->map(fn (LearningAudience $audience): array => [
                'id' => $audience->id, 'type' => $audience->type, 'source_id' => $audience->source_id,
                'tenant_id' => $audience->tenant_id, 'importance' => $audience->importance, 'stage' => $audience->stage,
                'description' => ['lt' => (string) $audience->getTranslation('description', 'lt', false), 'en' => (string) $audience->getTranslation('description', 'en', false)],
                'order' => $audience->order, 'label' => $audience->label(app()->getLocale()),
            ])->all(),
            'per_term' => $step->per_term, 'order' => $step->order, 'is_active' => $step->is_active,
            'sources' => $sources, 'source_documents' => $sourceDocuments,
        ];
    }

    /** @return list<'expired_document'|'updated_document'|'missing_source'> */
    private static function flags(KnowledgeItem $step): array
    {
        $flags = [];
        $baseline = $step->reviewed_at ?? $step->updated_at;
        $documents = $step->sources->map(fn ($source) => $source->document)->filter();

        if ($documents->contains(fn ($document): bool => $document->is_in_effect === false)) {
            $flags[] = 'expired_document';
        }
        if ($documents->contains(fn ($document): bool => $document->sharepoint_modified_at !== null && ($baseline === null || $document->sharepoint_modified_at->gt($baseline)))) {
            $flags[] = 'updated_document';
        }
        $hasBody = collect($step->getTranslations('description'))->contains(fn ($html): bool => trim(strip_tags((string) $html)) !== '');
        if (! $hasBody && $step->sources->filter(fn ($source): bool => $source->document_id !== null || filled($source->url))->isEmpty()) {
            $flags[] = 'missing_source';
        }

        return $flags;
    }
}
