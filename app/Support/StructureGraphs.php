<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * The structure graphs the frontend draws, read from the same JSON files, so a saved
 * graph key or node id can be checked against what actually exists.
 */
class StructureGraphs
{
    /** @var array<string, list<string>>|null */
    private static ?array $nodeIds = null;

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::nodeIds());
    }

    public static function hasNode(string $graph, string $node): bool
    {
        return in_array($node, self::nodeIds()[$graph] ?? [], true);
    }

    public static function directory(): string
    {
        return resource_path('js/Features/StructureGraph/graphs');
    }

    /** @return array<string, list<string>> */
    private static function nodeIds(): array
    {
        if (self::$nodeIds !== null) {
            return self::$nodeIds;
        }
        $nodeIds = [];
        foreach (File::json(self::directory().'/index.json') as $entry) {
            $graph = File::json(self::directory()."/{$entry['key']}.json");
            $nodeIds[$entry['key']] = array_column($graph['nodes'], 'id');
        }

        return self::$nodeIds = $nodeIds;
    }
}
