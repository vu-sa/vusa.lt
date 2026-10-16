<?php

namespace App\Support;

/**
 * Walks a parent_id type tree (institution or duty types) held as an [id => parent_id] map.
 */
final class TypeTree
{
    /**
     * The given ids plus every descendant.
     *
     * @param  array<int, int|null>  $parents
     * @param  list<int>  $typeIds
     * @return list<int>
     */
    public static function withDescendants(array $parents, array $typeIds): array
    {
        $children = [];

        foreach ($parents as $id => $parent) {
            if ($parent !== null) {
                $children[$parent][] = $id;
            }
        }

        $result = $typeIds;
        $frontier = $typeIds;

        while ($frontier !== []) {
            $frontier = array_values(array_unique(array_merge(...array_map(fn (int $id) => $children[$id] ?? [], $frontier))));
            $frontier = array_values(array_diff($frontier, $result));
            $result = [...$result, ...$frontier];
        }

        return $result;
    }
}
