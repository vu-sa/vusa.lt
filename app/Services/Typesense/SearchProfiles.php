<?php

namespace App\Services\Typesense;

use Illuminate\Support\Facades\Cache;

class SearchProfiles
{
    public const VERSION = 2;

    public static function cacheKey(string $collection): string
    {
        return 'typesense:search-profile:'.config('scout.prefix').$collection;
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(bool $admin = false): array
    {
        $profiles = [];
        $collections = array_unique([...TypesenseCollectionConfig::getPublicCollectionBaseNames(), ...TypesenseCollectionConfig::getAdminCollectionBaseNames()]);
        $versions = Cache::many(array_map(self::cacheKey(...), $collections));
        foreach ($collections as $collection) {
            $model = TypesenseCollectionConfig::getModelForCollection($collection);
            $settings = config('scout.typesense.model-settings.'.$model);
            if (! $settings) {
                continue;
            }
            $ready = config('scout.typesense.search-profile-version', self::VERSION) >= self::VERSION
                && (int) ($versions[self::cacheKey($collection)] ?? 0) === self::VERSION;
            $schema = collect($settings['collection-schema']['fields'])->keyBy('name');
            $params = $settings['search-parameters'];
            $fields = explode(',', $params['query_by']);
            $weights = explode(',', $params['query_by_weights'] ?? implode(',', array_fill(0, count($fields), 1)));
            if ($ready) {
                foreach (['body' => 2, 'search_text_lt' => 1, 'search_text_en' => 1] as $field => $weight) {
                    if ($schema->has($field)) {
                        $fields[] = $field;
                        $weights[] = $weight;
                    }
                }
            }
            foreach ($fields as $field) {
                if (! $schema->has($field) || ! str_starts_with($schema[$field]['type'], 'string')) {
                    throw new \LogicException("Invalid search field {$collection}.{$field}");
                }
            }
            $params['query_by'] = implode(',', $fields);
            $params['query_by_weights'] = implode(',', $weights);
            $params['infix'] = implode(',', array_map(fn ($field) => ($schema[$field]['infix'] ?? false) ? 'fallback' : 'off', $fields));
            $params['num_typos'] = implode(',', array_map(fn ($field) => in_array($field, ['email', 'phone', 'alias', 'location', 'document_year', 'document_date_formatted'], true) || str_starts_with($field, 'search_text_') ? 0 : 2, $fields));
            if ($admin) {
                $params['text_match_type'] = 'max_weight';
                $params['drop_tokens_threshold'] = 0;
            }
            $params['prefix'] = true;
            $params['exclude_fields'] = 'body,search_text_lt,search_text_en';
            $params['highlight_fields'] = implode(',', $fields);
            $params['highlight_start_tag'] = '⟦';
            $params['highlight_end_tag'] = '⟧';
            $params['snippet_threshold'] = 24;
            $profiles[$collection] = [
                'version' => $ready ? self::VERSION : 1,
                'parameters' => $params,
                'defaultSort' => $settings['collection-schema']['default_sorting_field'].':desc',
                'facetFields' => $schema->filter(fn ($field) => $field['facet'] ?? false)->keys()->all(),
                'sortFields' => $schema->filter(fn ($field) => $field['sort'] ?? in_array($field['type'], ['int32', 'int64', 'float', 'bool'], true))->keys()->all(),
            ];
        }

        return $profiles;
    }
}
