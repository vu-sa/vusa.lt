<?php

namespace App\Services;

use App\Actions\ResolveLearningPath;
use App\Enums\LearningImportance;
use App\Models\GlossaryTerm;
use App\Models\KnowledgeItem;
use App\Models\User;
use App\Services\Learning\ResolvedLearningItem;

/**
 * @phpstan-type TermPayload array{id: string, term: string, abbreviation: string, definition: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}}
 * @phpstan-type GlossaryPayload array{id: string, term: string, abbreviation: string, definition: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}, items: list<array{id: string, title: string, url: string}>, itemsCount: int, priority: array{rank: int, importance: \App\Enums\LearningImportance|null, sectionOrder: int, itemOrder: int}}
 * @phpstan-type GlossaryTermPayload array{id: string, term: string, abbreviation: string, definition: string, translations: array{term: array{lt: string, en: string}, abbreviation: array{lt: string, en: string}, definition: array{lt: string, en: string}}, items: list<array{id: string, title: string, url: string, inPath: bool, importance: \App\Enums\LearningImportance|null, state: string, section: array{id: string, title: string}|null, source: array{type: 'document'|'url', url: string|null, documentId: int|null, documentStatus: \App\Enums\DocumentStatus|null, title: string|null, private: bool, thumbnailUrl: string|null}|null}>}
 */
class LearningAdminPresenter
{
    /** @return list<GlossaryPayload> */
    public static function glossary(string $locale, ?User $viewer = null): array
    {
        $viewer ??= auth()->user();
        $path = ResolveLearningPath::execute($viewer);
        $resolved = $path->knowledge->keyBy('item.id');
        $result = [];
        foreach (GlossaryTerm::query()->with(['knowledgeItems.section'])->get() as $term) {
            $linked = $term->knowledgeItems->map(fn (KnowledgeItem $item): array => self::priority($item, $resolved->get($item->id)))
                ->sortBy(fn (array $priority): array => [$priority['rank'], $priority['sectionOrder'], $priority['itemOrder']])->first()
                ?? ['rank' => 3, 'importance' => null, 'sectionOrder' => PHP_INT_MAX, 'itemOrder' => PHP_INT_MAX];
            $items = [];
            foreach ($term->knowledgeItems as $item) {
                $items[] = ['id' => $item->id, 'title' => (string) $item->getTranslation('title', $locale), 'url' => route('knowledgeItems.show', $item)];
            }
            $result[] = [...self::term($term, $locale), 'items' => $items, 'itemsCount' => count($items), 'priority' => $linked];
        }
        $collator = new \Collator($locale);
        usort($result, fn (array $a, array $b): int => [$a['priority']['rank'], $a['priority']['sectionOrder'], $a['priority']['itemOrder']]
            <=> [$b['priority']['rank'], $b['priority']['sectionOrder'], $b['priority']['itemOrder']]
            ?: (int) $collator->compare($a['term'], $b['term']));

        return $result;
    }

    /** @return GlossaryTermPayload */
    public static function glossaryTerm(GlossaryTerm $term, User $viewer, string $locale): array
    {
        $resolved = ResolveLearningPath::execute($viewer)->knowledge->keyBy('item.id');
        $items = [];
        foreach ($term->load(['knowledgeItems' => fn ($query) => $query->active()->with(['section', 'sources.document.institution.tenant'])])->knowledgeItems as $item) {
            $match = $resolved->get($item->id);
            $items[] = [
                'id' => $item->id, 'title' => (string) $item->getTranslation('title', $locale), 'url' => route('knowledgeItems.show', $item),
                'inPath' => $match !== null, 'importance' => $match?->importance, 'state' => $match?->state ?? 'pending',
                'section' => $item->section ? ['id' => $item->section->id, 'title' => (string) $item->section->getTranslation('title', $locale)] : null,
                'source' => KnowledgeBasePresenter::source($item, $locale),
                'priority' => self::priority($item, $match),
            ];
        }
        usort($items, fn (array $a, array $b): int => [$a['priority']['rank'], $a['priority']['sectionOrder'], $a['priority']['itemOrder'], $a['id']]
            <=> [$b['priority']['rank'], $b['priority']['sectionOrder'], $b['priority']['itemOrder'], $b['id']]);
        foreach ($items as &$item) {
            unset($item['priority']);
        }

        return [...self::term($term, $locale), 'items' => $items];
    }

    /** @return array{rank: int, importance: LearningImportance|null, sectionOrder: int, itemOrder: int} */
    private static function priority(KnowledgeItem $item, ?ResolvedLearningItem $resolved): array
    {
        return ['rank' => $resolved === null ? 3 : (int) array_search($resolved->importance, LearningImportance::cases(), true),
            'importance' => $resolved?->importance, 'sectionOrder' => $item->section?->order ?? PHP_INT_MAX, 'itemOrder' => $item->order];
    }

    /** @return TermPayload */
    private static function term(GlossaryTerm $term, string $locale): array
    {
        return [
            'id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale),
            'abbreviation' => (string) $term->getTranslation('abbreviation', $locale), 'definition' => (string) $term->getTranslation('definition', $locale),
            'translations' => [
                'term' => ['lt' => (string) $term->getTranslation('term', 'lt', false), 'en' => (string) $term->getTranslation('term', 'en', false)],
                'abbreviation' => ['lt' => (string) $term->getTranslation('abbreviation', 'lt', false), 'en' => (string) $term->getTranslation('abbreviation', 'en', false)],
                'definition' => ['lt' => (string) $term->getTranslation('definition', 'lt', false), 'en' => (string) $term->getTranslation('definition', 'en', false)],
            ],
        ];
    }

    /** @return list<array{id: string, term: string, abbreviation: string}> */
    public static function glossaryOptions(string $locale): array
    {
        $options = [];
        foreach (GlossaryTerm::query()->get() as $term) {
            /** @var GlossaryTerm $term */
            $options[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale), 'abbreviation' => (string) $term->getTranslation('abbreviation', $locale)];
        }
        usort($options, fn (array $a, array $b): int => strcoll($a['term'], $b['term']));

        return $options;
    }
}
