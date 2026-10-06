<?php

namespace App\Services\Typesense;

use App\Models\Document;
use App\Settings\DocumentSettings;
use Illuminate\Support\Collection;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

class DocumentRecommendations
{
    public function __construct(private readonly Client $client, private readonly DocumentSettings $settings) {}

    /** @return string[] */
    public function matchingIds(string $query): array
    {
        $rules = $this->enabledRules();
        if ($rules->isEmpty()) {
            return [];
        }
        $query = trim($query);
        if ($query === '' || $query === '*') {
            $ids = $rules->filter(fn ($rule) => $rule['show_without_query'])->pluck('document_id')->all();
        } else {
            try {
                $result = $this->client->collections[$this->collectionName()]->documents->search([
                    'q' => $query, 'query_by' => 'phrase_lt,phrase_en', 'prefix' => true,
                    'num_typos' => 0, 'drop_tokens_threshold' => 0,
                    'sort_by' => 'position:asc', 'per_page' => 200, 'include_fields' => 'document_id',
                ]);
            } catch (ObjectNotFound) {
                // Not synchronized yet (fresh deploy): recommendations are an extra, never a failure.
                return [];
            }
            $ids = collect($result['hits'])->pluck('document.document_id')->unique()->values()->all();
        }

        $activeIds = Document::query()->whereIn('id', $ids)->where('is_active', true)->pluck('id')->map(strval(...))->all();

        return array_values(array_intersect($ids, $activeIds, $rules->pluck('document_id')->all()));
    }

    /**
     * Rebuild the phrase collection from the saved rules; runs on settings save and in `typesense:apply-search-config`.
     */
    public function synchronize(): void
    {
        // Search indexing is off (the test suite's default): there is no index to keep in step.
        if (app(EngineManager::class)->engine('typesense') instanceof NullEngine) {
            return;
        }
        $name = $this->collectionName();
        try {
            $this->client->collections[$name]->delete();
        } catch (ObjectNotFound) {
        }
        $this->client->collections->create(['name' => $name, 'fields' => [
            ['name' => 'document_id', 'type' => 'string'],
            ['name' => 'phrase_lt', 'type' => 'string', 'locale' => 'lt', 'stem' => true],
            ['name' => 'phrase_en', 'type' => 'string', 'locale' => 'en', 'stem' => true],
            ['name' => 'position', 'type' => 'int32'],
        ]]);

        $rules = $this->enabledRules();
        $documents = Document::query()->whereIn('id', $rules->pluck('document_id'))->get(['id', 'language'])->keyBy('id');
        $rows = [];
        foreach ($rules as $position => $rule) {
            $document = $documents->get($rule['document_id']);
            if (! $document) {
                continue;
            }
            $english = $document->language_code === 'en';
            foreach ($rule['phrases'] as $index => $phrase) {
                $rows[] = [
                    'id' => $position.'-'.$index, 'document_id' => (string) $document->id,
                    'phrase_lt' => $english ? '' : $phrase, 'phrase_en' => $english ? $phrase : '',
                    'position' => $position,
                ];
            }
        }
        if ($rows !== []) {
            $this->client->collections[$name]->documents->import($rows, ['action' => 'create']);
        }
    }

    private function collectionName(): string
    {
        return config('scout.prefix').'document_recommendation_phrases';
    }

    /** @return Collection<int, array{document_id: string, phrases: string[], enabled: true, show_without_query: bool}> */
    private function enabledRules(): Collection
    {
        return collect($this->settings->recommendations)->filter(fn ($rule) => $rule['enabled'])->values();
    }
}
