<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\SharepointFolder;
use Illuminate\Support\Facades\DB;

/**
 * The archive drive's folder tree, held in memory for one discovery run and mirrored in
 * `sharepoint_folders`. Paths come from walking parent ids, never from Graph's
 * parentReference.path, which the delta feed does not promise to send.
 */
class SharepointFolderTree
{
    /**
     * @param  array<string, array{name: string, parent: ?string}>  $folders  by drive item id
     */
    private function __construct(private array $folders) {}

    public static function load(): self
    {
        $folders = [];

        foreach (SharepointFolder::query()->toBase()->get(['drive_item_id', 'parent_id', 'name']) as $row) {
            $folders[(string) $row->drive_item_id] = ['name' => (string) $row->name, 'parent' => $row->parent_id];
        }

        return new self($folders);
    }

    /**
     * Folder path below the drive root ("Dokumentų sistema/Protokolai"), '' for the root itself,
     * null when the chain cannot be resolved.
     */
    public function pathOf(?string $folderId): ?string
    {
        $segments = [];
        $seen = [];

        while ($folderId !== null) {
            // An unknown link or a cycle means the tree is incomplete; say so instead of guessing.
            if (! isset($this->folders[$folderId]) || isset($seen[$folderId])) {
                return null;
            }

            $seen[$folderId] = true;
            $folder = $this->folders[$folderId];

            if ($folder['name'] !== '') {
                array_unshift($segments, $folder['name']);
            }

            $folderId = $folder['parent'];
        }

        return implode('/', $segments);
    }

    /**
     * Apply the feed's folders. A full listing replaces the tree; a delta updates it. Nothing is stored yet.
     *
     * @param  array<string, array{name: string, parent: ?string}>  $changed  folders the feed sent (root with name '')
     * @param  list<string>  $deleted  drive item ids the feed reported deleted
     * @return array<string, string> new path by old path, for every folder whose path changed
     */
    public function apply(array $changed, array $deleted, bool $replace): array
    {
        $before = new self($this->folders);
        $this->folders = $replace ? $changed : array_merge(array_diff_key($this->folders, array_flip($deleted)), $changed);

        $moves = [];

        foreach (array_keys($this->folders) as $id) {
            $old = $before->pathOf($id);
            $new = $this->pathOf($id);

            if ($old !== null && $new !== null && $old !== '' && $old !== $new) {
                $moves[$old] = $new;
            }
        }

        return $moves;
    }

    /**
     * Rewrite stored document paths under moved folders, since Graph does not re-send their files.
     *
     * Each path is mapped once, from where it was, by its deepest moved folder: a renamed folder inside
     * a renamed folder keeps its own new name, and chained moves (A→B, B→C) never move a file twice.
     *
     * @param  array<string, string>  $moves  new path by old path
     */
    public static function moveDocumentPaths(array $moves): int
    {
        if ($moves === []) {
            return 0;
        }

        // Every moved folder lies under one of these, so they alone select the affected rows.
        $outermost = [];

        foreach (collect(array_keys($moves))->sortBy(fn (string $path): int => mb_strlen($path)) as $old) {
            if (! collect($outermost)->contains(fn (string $parent): bool => str_starts_with($old, $parent.'/'))) {
                $outermost[] = $old;
            }
        }

        $updated = 0;

        Document::query()
            ->where(function ($query) use ($outermost): void {
                foreach ($outermost as $old) {
                    $query->orWhere('sharepoint_path', $old)->orWhere('sharepoint_path', 'like', addcslashes($old, '\\%_').'/%');
                }
            })
            ->select(['id', 'sharepoint_path'])
            ->chunkById(500, function ($documents) use ($moves, &$updated): void {
                foreach ($documents as $document) {
                    $path = (string) $document->sharepoint_path;
                    $new = self::mapPath($path, $moves);

                    if ($new === null || $new === $path) {
                        continue;
                    }

                    // A plain update: a path rewrite is not a change anyone needs logged or reindexed.
                    Document::query()->whereKey($document->id)->update(['sharepoint_path' => $new]);
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * @param  array<string, string>  $moves
     */
    private static function mapPath(string $path, array $moves): ?string
    {
        $segments = explode('/', $path);

        for ($length = count($segments); $length > 0; $length--) {
            $prefix = implode('/', array_slice($segments, 0, $length));

            if (isset($moves[$prefix])) {
                return implode('/', [$moves[$prefix], ...array_slice($segments, $length)]);
            }
        }

        return null;
    }

    public function save(): void
    {
        DB::transaction(function (): void {
            $ids = array_keys($this->folders);

            SharepointFolder::query()->whereNotIn('drive_item_id', $ids === [] ? [''] : $ids)->delete();

            foreach (array_chunk($this->folders, 500, true) as $chunk) {
                SharepointFolder::query()->upsert(
                    array_map(fn (string $id, array $folder): array => [
                        'drive_item_id' => $id,
                        'parent_id' => $folder['parent'],
                        'name' => $folder['name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], array_keys($chunk), $chunk),
                    ['drive_item_id'],
                    ['parent_id', 'name', 'updated_at'],
                );
            }
        });
    }
}
