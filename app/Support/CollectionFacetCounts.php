<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Filter-panel counts for database-backed admin collections (`useDatabaseCollectionSource`).
 *
 * Each value is counted by replaying the endpoint's own query for a copy of the request with that
 * facet set to just that value, so a count always means what picking it would list, however the
 * endpoint reads its filters. As in the local source, a facet ignores its own selection.
 */
final class CollectionFacetCounts
{
    /** Values beyond this stay uncounted (the panel shows no number) rather than fan out further. */
    public const int MAX_VALUES = 80;

    private const int CACHE_SECONDS = 60;

    /**
     * Counts for the values the page asked about (`facet_values`), or null unless it asked.
     *
     * @template TRequest of FormRequest
     * @template TModel of Model
     *
     * @param  TRequest  $request
     * @param  list<string>  $fields  Facet fields the endpoint understands; others are ignored.
     * @param  Closure(TRequest): Builder<TModel>  $query  The endpoint's filtered, unpaginated query.
     * @return array<string, array<string, int>>|null
     */
    public static function forRequest(FormRequest $request, array $fields, Closure $query): ?array
    {
        if (! $request->boolean('include_facets')) {
            return null;
        }

        $requested = self::requestedValues($request, $fields);
        $single = array_intersect((array) json_decode((string) $request->input('facet_single', '[]'), true), $fields);

        if ($requested === []) {
            return [];
        }

        $baseInput = collect($request->query())
            ->except(['page', 'per_page', 'sorting', 'include_facets', 'facet_values', 'facet_single'])
            ->all();
        $cacheKey = 'collection-facets:'.md5(implode('|', [
            (string) $request->user()?->getAuthIdentifier(),
            $request->route()?->getName() ?? $request->path(),
            json_encode($baseInput),
            json_encode($requested),
            json_encode($single),
        ]));

        return Cache::remember($cacheKey, self::CACHE_SECONDS, function () use ($request, $requested, $single, $baseInput, $query): array {
            $counts = [];

            foreach ($requested as $field => $values) {
                foreach ($values as $value) {
                    $variant = self::variant($request, $baseInput, $field, $value, in_array($field, $single, true));

                    if ($variant !== null) {
                        $counts[$field][$value] = $query($variant)->toBase()->getCountForPagination();
                    }
                }
            }

            return $counts;
        });
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, list<string>>
     */
    private static function requestedValues(FormRequest $request, array $fields): array
    {
        $decoded = json_decode((string) $request->input('facet_values', '{}'), true);
        $requested = [];
        $budget = self::MAX_VALUES;

        foreach ($fields as $field) {
            $values = is_array($decoded) ? ($decoded[$field] ?? null) : null;

            if (! is_array($values)) {
                continue;
            }

            $values = array_values(array_unique(array_filter($values, fn ($value): bool => is_string($value) && $value !== '')));
            $values = array_slice($values, 0, $budget);
            $budget -= count($values);

            if ($values !== []) {
                $requested[$field] = $values;
            }
        }

        return $requested;
    }

    /**
     * The request as the page would have sent it with `$field` narrowed to `$value`: the plain
     * param (dots arrive as underscores in a query string) and the `filters` JSON copy.
     *
     * @template TRequest of FormRequest
     *
     * @param  TRequest  $request
     * @param  array<string, mixed>  $baseInput
     * @return TRequest|null
     */
    private static function variant(FormRequest $request, array $baseInput, string $field, string $value, bool $single): ?FormRequest
    {
        $plainKey = str_replace('.', '_', $field);
        $input = $baseInput;
        $input[$plainKey] = $single ? $value : [$value];

        $filters = json_decode((string) ($baseInput['filters'] ?? '{}'), true);
        $filters = is_array($filters) ? $filters : [];
        $filters[$field] = $single ? $value : [$value];
        $input['filters'] = json_encode($filters);

        // A fresh instance, so validated() reads the variant rather than the original payload.
        $variant = $request::createFrom($request);
        $variant->query->replace($input);
        $variant->setContainer(app())->setRedirector(app('redirect'));

        try {
            $variant->validateResolved();
        } catch (ValidationException) {
            return null;
        }

        return $variant;
    }
}
