<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Exceptions\SharepointDeltaExpiredException;
use App\Exceptions\SharepointThrottledException;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Services\SharepointGraphService;
use App\Settings\DocumentDiscoverySettings;
use Illuminate\Database\DetectsLostConnections;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\DriveItem;

/**
 * Mirrors the SharePoint document archive into `documents`: new files arrive as pending, changed
 * files refresh their metadata, deleted files are marked removed. Publication is never changed here.
 *
 * The deltaLink is stored only after a run fully succeeds, so a failed run is replayed, not skipped.
 *
 * @phpstan-type FeedItem array{id: string, name: ?string, deleted: bool, isFile: bool, isFolder: bool, isRoot: bool, parentId: ?string, listItemUniqueId: ?string, siteId: ?string, listId: ?string, path: ?string, webUrl: ?string, modifiedAt: ?Carbon}
 */
class SharepointDocumentDiscovery
{
    use DetectsLostConnections;

    /** A run that would remove more than this many documents (or share, if larger) is refused. */
    public const MAX_REMOVALS = 25;

    public const MAX_REMOVAL_SHARE = 0.05;

    private const string LOCK = 'sharepoint-document-discovery';

    private const string FAILURE_STREAKS = 'sharepoint-document-discovery:failure-streaks';

    private const int GIVE_UP_AFTER = 3;

    private const string THROTTLED_UNTIL = 'sharepoint-document-discovery:throttled';

    public function __construct(private DocumentDiscoverySettings $settings) {}

    /**
     * @param  int|null  $limit  Add at most this many new files, in folder order. Repeat to add the archive in
     *                           batches; a limited run removes nothing and keeps its place in the change feed unsaved.
     */
    public function run(bool $full = false, bool $dryRun = false, bool $allowMassRemoval = false, ?int $limit = null): DiscoveryResult
    {
        if (Cache::has(self::THROTTLED_UNTIL)) {
            return DiscoveryResult::aborted('throttled', $full);
        }

        $lock = Cache::lock(self::LOCK, 3600);

        if (! $lock->get()) {
            return DiscoveryResult::aborted('already_running', $full);
        }

        try {
            return $this->discover($full, $dryRun, $allowMassRemoval, $limit);
        } catch (SharepointThrottledException $e) {
            // The checkpoint is untouched, so the next run after the pause replays this one.
            Log::warning('SharePoint throttled document discovery; pausing it', ['seconds' => $e->retryAfterSeconds]);
            Cache::put(self::THROTTLED_UNTIL, true, $e->retryAfterSeconds);

            return DiscoveryResult::aborted('throttled', $full);
        } finally {
            $lock->release();
        }
    }

    /**
     * The document row of a file picked in SharePoint's own file picker, importing it now as a run
     * would (pending) when discovery has not reached it yet.
     *
     * @throws PickedDocumentException when the file is not an archive document or cannot be read
     */
    public function importPicked(string $siteId, string $listId, string $listItemUniqueId): Document
    {
        $uniqueId = strtolower(trim($listItemUniqueId, '{} '));
        $existing = Document::query()->where('sharepoint_id', $uniqueId)->first();

        if ($existing !== null) {
            return $existing;
        }

        $graph = $this->makeGraphService();

        try {
            $driveItem = $graph->getDriveItemByListItem($siteId, $listId, $uniqueId);
        } catch (\Throwable $e) {
            Log::warning('A picked SharePoint file could not be read', ['list_item_unique_id' => $uniqueId, 'error' => $e->getMessage()]);

            throw PickedDocumentException::notFound();
        }

        $item = [...$this->normalize($driveItem), 'listItemUniqueId' => $uniqueId, 'siteId' => $siteId, 'listId' => $listId];
        $item['path'] = SharepointFolderTree::load()->pathOf($item['parentId']) ?? $item['path'];
        $inArchiveDrive = $driveItem->getParentReference()?->getDriveId() === config('filesystems.sharepoint.archive_drive_id');

        if (! $inArchiveDrive || ! $item['isFile'] || ! $this->inDiscoveryFolder($item['path']) || ! $this->isDocumentFile($item['name'])) {
            throw PickedDocumentException::outsideArchive((string) $item['name']);
        }

        $fields = $graph->getListItemsForDriveItems([$item['id']])['items'][$item['id']]['fields'] ?? null;

        if ($fields === null) {
            throw PickedDocumentException::notFound();
        }

        try {
            $this->upsertDocument(null, $item, $fields, isBaseline: false, dryRun: false, result: new DiscoveryResult,
                resolveInstitution: SharepointDocumentFields::institutionFor(...));
        } catch (UniqueConstraintViolationException) {
            // A discovery run stored the same file meanwhile.
        }

        return Document::query()->where('sharepoint_id', $uniqueId)->firstOrFail();
    }

    /**
     * Build the Graph client for the archive drive. Extracted so tests can stub it.
     */
    protected function makeGraphService(): SharepointGraphService
    {
        return new SharepointGraphService(driveId: config('filesystems.sharepoint.archive_drive_id'));
    }

    private function discover(bool $full, bool $dryRun, bool $allowMassRemoval, ?int $limit): DiscoveryResult
    {
        $graph = $this->makeGraphService();
        $link = $full ? null : $this->settings->delta_link;

        try {
            [$items, $deltaLink] = $this->readFeed($graph, $link);
        } catch (SharepointDeltaExpiredException) {
            Log::warning('SharePoint delta link expired, starting a full document discovery');
            $link = null;
            [$items, $deltaLink] = $this->readFeed($graph, null);
        }

        $isFull = $link === null;
        // The first listing ever meets years of files nobody imported; they start hidden so
        // "Laukia" only collects what arrives afterwards. A later forced full run is not a first one.
        $isBaseline = $this->settings->last_full_run_at === null;
        $result = new DiscoveryResult(full: $isFull, dryRun: $dryRun, baseline: $isBaseline, limited: $limit !== null);

        // Folders first: every file's path is rebuilt from them, and a renamed folder moves its contents.
        // Applied in memory only; nothing is written until the run has passed the removal guard.
        $tree = SharepointFolderTree::load();
        $folderMoves = $tree->apply(
            changed: $items->filter(fn (array $item): bool => ! $item['deleted'] && ($item['isFolder'] || $item['isRoot']))
                ->mapWithKeys(fn (array $item): array => [$item['id'] => [
                    'name' => $item['isRoot'] ? '' : (string) $item['name'],
                    'parent' => $item['isRoot'] ? null : $item['parentId'],
                ]])->all(),
            deleted: $items->filter(fn (array $item): bool => $item['deleted'])->keys()->all(),
            replace: $isFull,
        );
        // '' is the drive root; null means neither the tree nor Graph could place the item.
        $items = $items->map(function (array $item) use ($tree): array {
            if (! $item['isFolder'] && ! $item['deleted']) {
                // Graph's own path is only a fallback when the tree cannot place the item.
                $item['path'] = $tree->pathOf($item['parentId']) ?? $item['path'];
            }

            return $item;
        });

        // The whole library, so a full listing can tell a deleted file from one merely moved.
        $files = $items->filter(fn (array $item): bool => ! $item['deleted'] && $item['isFile'] && $item['listItemUniqueId'] !== null);

        $existing = $this->documentsWhereIn('sharepoint_id', $files->pluck('listItemUniqueId')->all())
            ->keyBy(fn (Document $document): string => strtolower($document->sharepoint_id));

        // New rows only come from the discovery folder; documents imported from elsewhere keep syncing.
        $tracked = $files->filter(fn (array $item): bool => $existing->has($item['listItemUniqueId'])
            || ($this->inDiscoveryFolder($item['path']) && $this->isDocumentFile($item['name'])));

        // A sample has not seen the whole archive, so it cannot judge what is gone.
        $removals = $limit !== null ? new Collection : $this->removalCandidates(
            deletedIds: $items->filter(fn (array $item): bool => $item['deleted'])->keys()->all(),
            listIds: $files->pluck('listId')->filter()->unique()->values()->all(),
            seenUniqueIds: $files->pluck('listItemUniqueId')->all(),
            isFull: $isFull,
        );

        $inScope = Document::query()->whereNull('removed_from_sharepoint_at')->count();
        $removalLimit = max(self::MAX_REMOVALS, (int) ceil($inScope * self::MAX_REMOVAL_SHARE));

        if ($removals->count() > $removalLimit && ! $allowMassRemoval) {
            Log::error('SharePoint document discovery refused a mass removal', [
                'would_remove' => $removals->count(),
                'limit' => $removalLimit,
                'full' => $isFull,
            ]);

            return DiscoveryResult::aborted('mass_removal', $isFull, $removals->count());
        }

        if (! $dryRun) {
            DB::transaction(function () use ($folderMoves, $tree): void {
                SharepointFolderTree::moveDocumentPaths($folderMoves);
                $tree->save();
            });
        }

        // Unchanged, already-linked files need no field lookup; on a delta run that is nearly all of them.
        [$needsFields, $unchanged] = $tracked->partition(function (array $item) use ($existing): bool {
            $document = $existing->get($item['listItemUniqueId']);

            return $document === null
                || $document->sharepoint_drive_item_id !== $item['id']
                || $document->removed_from_sharepoint_at !== null
                || $item['modifiedAt'] === null
                || $document->sharepoint_modified_at === null
                || ! $document->sharepoint_modified_at->equalTo($item['modifiedAt']);
        });

        // A move or rename leaves the modification time alone, so location is reconciled without a lookup.
        foreach ($unchanged as $item) {
            $document = $existing->get($item['listItemUniqueId']);
            $this->fillLocation($document, $item);

            if ($document->isDirty()) {
                $result->updated++;

                if (! $dryRun) {
                    $document->save();
                }
            }
        }

        if ($limit !== null) {
            [$known, $new] = $needsFields->partition(fn (array $item): bool => $existing->has($item['listItemUniqueId']));
            // Folder order, so repeated limited runs work through the archive predictably.
            $needsFields = $known->concat($new->sortBy(fn (array $item): string => $item['path'].'/'.$item['name'], SORT_NATURAL | SORT_FLAG_CASE)->take($limit));
        }

        $failedIds = [];
        ['items' => $listItems, 'transient' => $transientIds] = $graph->getListItemsForDriveItems($needsFields->pluck('id')->values()->all());
        $institutions = [];
        $resolveInstitution = function (string $label) use (&$institutions): ?Institution {
            return array_key_exists($label, $institutions)
                ? $institutions[$label]
                : $institutions[$label] = SharepointDocumentFields::institutionFor($label);
        };

        foreach ($needsFields as $item) {
            $listItem = $listItems[$item['id']] ?? null;

            if ($listItem === null) {
                $result->failed++;
                $failedIds[] = $item['id'];

                continue;
            }

            try {
                $this->upsertDocument($existing->get($item['listItemUniqueId']), $item, $listItem['fields'], $isBaseline, $dryRun, $result, $resolveInstitution);
            } catch (\Throwable $e) {
                if (! $this->isItemError($e)) {
                    throw $e;
                }

                Log::warning('SharePoint document discovery could not store a file', [
                    'drive_item_id' => $item['id'],
                    'error' => $e->getMessage(),
                ]);
                $result->failed++;
                $failedIds[] = $item['id'];
            }
        }

        $result->removed = $removals->count();

        if ($dryRun) {
            return $result;
        }

        $removals->each(function (Document $document): void {
            $document->removed_from_sharepoint_at = now();
            $document->save();
        });

        $result->rematched = $this->rematchInstitutions($resolveInstitution);

        $blocking = $this->blockingFailures(
            failedIds: $failedIds,
            transientIds: array_values(array_intersect($failedIds, $transientIds)),
            succeededIds: $needsFields->pluck('id')->diff($failedIds)->values()->all(),
            sawEverything: $isFull && $limit === null,
        );
        $result->givenUp = count($failedIds) - count($blocking);

        if ($blocking !== [] || $limit !== null) {
            if ($blocking !== []) {
                Log::warning('SharePoint document discovery left items for the next run', ['failed' => count($blocking)]);
            }

            return $result;
        }

        if ($result->givenUp > 0) {
            Log::error('SharePoint document discovery gave up on items that keep failing; they are retried when they change or at the weekly full listing', [
                'drive_item_ids' => array_values(array_diff($failedIds, $blocking)),
            ]);
        }

        $this->settings->delta_link = $deltaLink;
        $this->settings->last_run_at = now()->toIso8601String();
        $this->settings->skipped_files = count($this->givenUpIds());

        if ($isFull) {
            $this->settings->last_full_run_at = now()->toIso8601String();
        }

        $this->settings->save();

        Log::info('SharePoint document discovery finished', $result->toArray());

        return $result;
    }

    /**
     * @param  FeedItem  $item
     * @param  array<string, mixed>  $fields
     * @param  \Closure(string): ?Institution  $resolveInstitution
     */
    private function upsertDocument(?Document $document, array $item, array $fields, bool $isBaseline, bool $dryRun, DiscoveryResult $result, \Closure $resolveInstitution): void
    {
        $isNew = $document === null;
        $fileChanged = ! $isNew && $document->sharepoint_modified_at !== null
            && ! $document->sharepoint_modified_at->equalTo($item['modifiedAt'] ?? $document->sharepoint_modified_at);
        $driveItemChanged = ! $isNew && $document->sharepoint_drive_item_id !== $item['id'];
        $restored = ! $isNew && $document->removed_from_sharepoint_at !== null;

        if ($isNew) {
            $document = new Document([
                'sharepoint_id' => $item['listItemUniqueId'],
                'status' => $isBaseline ? DocumentStatus::Hidden : DocumentStatus::Pending,
            ]);
            $document->title = $item['name'];
            $document->sync_status = 'success';
            $document->checked_at = now();
        } elseif ($restored) {
            $document->removed_from_sharepoint_at = null;
        }

        $document->fill([
            'sharepoint_site_id' => $item['siteId'],
            'sharepoint_list_id' => $item['listId'],
            'sharepoint_modified_at' => $item['modifiedAt'],
        ]);
        $this->fillLocation($document, $item);
        // `eTag` is the per-document sync's marker that the public link and .url target are current;
        // leaving it alone lets that sync see the change and redo both.

        SharepointDocumentFields::apply($document, $fields, $resolveInstitution);

        if ($document->institution_id === null && $document->sharepoint_institution_label !== null) {
            $result->unmatchedLabels[$document->sharepoint_institution_label] = true;
        }

        if (! $dryRun) {
            $document->save();

            // A changed, moved or restored published file may have a new .url target or a lost link.
            if (($fileChanged || $driveItemChanged || $restored) && $document->isPublished()) {
                SyncDocumentFromSharePointJob::dispatch($document);
            }
        }

        $isNew ? $result->created++ : $result->updated++;

        if ($restored) {
            $result->restored++;
        }
    }

    /**
     * @param  FeedItem  $item
     */
    private function fillLocation(Document $document, array $item): void
    {
        $document->fill([
            'name' => $item['name'],
            'sharepoint_drive_item_id' => $item['id'],
            'sharepoint_web_url' => $item['webUrl'],
        ]);

        // Never trade a known folder for an unknown one; the root is stored as null.
        if ($item['path'] !== null) {
            $document->sharepoint_path = $item['path'] === '' ? null : $item['path'];
        }
    }

    /**
     * A malformed file's data fails only that file; anything else (Graph, a lost connection) ends the run.
     */
    private function isItemError(\Throwable $e): bool
    {
        if ($e instanceof QueryException) {
            return ! $this->causedByLostConnection($e);
        }

        return $e instanceof \InvalidArgumentException || $e instanceof \ValueError || $e instanceof \TypeError;
    }

    /**
     * A padalinys label that matched nothing is retried every run, so creating or renaming the
     * institution here is enough — the file need not change in SharePoint.
     *
     * @param  \Closure(string): ?Institution  $resolveInstitution
     */
    private function rematchInstitutions(\Closure $resolveInstitution): int
    {
        $rematched = 0;

        $labels = Document::query()
            ->whereNull('institution_id')
            ->whereNotNull('sharepoint_institution_label')
            ->distinct()
            ->pluck('sharepoint_institution_label');

        foreach ($labels as $label) {
            $institution = $resolveInstitution((string) $label);

            if ($institution === null) {
                continue;
            }

            Document::query()
                ->whereNull('institution_id')
                ->where('sharepoint_institution_label', $label)
                ->get()
                ->each(function (Document $document) use ($institution, &$rematched): void {
                    $document->institution()->associate($institution);
                    $document->save();
                    $rematched++;
                });
        }

        return $rematched;
    }

    /**
     * Failures that still hold the checkpoint back. A file whose own data fails GIVE_UP_AFTER runs in a
     * row stops doing so, or one broken file would replay the same feed forever. Transient failures
     * (throttling, an outage, a refused token) say nothing about the file, so they always hold it back.
     *
     * @param  list<string>  $failedIds
     * @param  list<string>  $transientIds  the subset of $failedIds that may pass on its own
     * @param  list<string>  $succeededIds
     * @param  bool  $sawEverything  a complete full listing: an item it did not fail on is fine or gone
     * @return list<string>
     */
    private function blockingFailures(array $failedIds, array $transientIds, array $succeededIds, bool $sawEverything): array
    {
        /** @var array<string, int> $streaks */
        $streaks = Cache::get(self::FAILURE_STREAKS, []);
        $streaks = $sawEverything
            ? array_intersect_key($streaks, array_flip($failedIds))
            : array_diff_key($streaks, array_flip($succeededIds));

        $transient = array_flip($transientIds);

        foreach ($failedIds as $id) {
            if (! isset($transient[$id])) {
                $streaks[$id] = ($streaks[$id] ?? 0) + 1;
            }
        }

        Cache::forever(self::FAILURE_STREAKS, $streaks);

        return array_values(array_filter($failedIds, fn (string $id): bool => isset($transient[$id]) || ($streaks[$id] ?? 0) < self::GIVE_UP_AFTER));
    }

    /**
     * Files skipped after failing repeatedly; they wait for a change or the weekly full listing.
     *
     * @return list<string>
     */
    private function givenUpIds(): array
    {
        /** @var array<string, int> $streaks */
        $streaks = Cache::get(self::FAILURE_STREAKS, []);

        return array_keys(array_filter($streaks, fn (int $streak): bool => $streak >= self::GIVE_UP_AFTER));
    }

    /**
     * Read every page of the change feed. Later entries for the same item win.
     *
     * @return array{0: Collection<string, FeedItem>, 1: string}
     */
    private function readFeed(SharepointGraphService $graph, ?string $link): array
    {
        $items = collect();
        $deltaLink = null;

        do {
            $page = $graph->getDriveDeltaPage($link);

            foreach ($page['items'] as $driveItem) {
                $item = $this->normalize($driveItem);
                $items->put($item['id'], $item);
            }

            $link = $page['nextLink'];
            $deltaLink = $page['deltaLink'] ?? $deltaLink;
        } while ($link !== null);

        if ($deltaLink === null) {
            throw new \RuntimeException('SharePoint delta feed ended without a deltaLink');
        }

        return [$items, $deltaLink];
    }

    /**
     * @return FeedItem
     */
    private function normalize(DriveItem $driveItem): array
    {
        $ids = $driveItem->getSharepointIds();
        $modified = $driveItem->getLastModifiedDateTime();
        $uniqueId = $ids?->getListItemUniqueId();

        return [
            'id' => (string) $driveItem->getId(),
            'name' => $driveItem->getName(),
            'deleted' => $driveItem->getDeleted() !== null,
            'isFile' => $driveItem->getFile() !== null && $driveItem->getFolder() === null,
            'isFolder' => $driveItem->getFolder() !== null && $driveItem->getRoot() === null,
            'isRoot' => $driveItem->getRoot() !== null,
            'parentId' => $driveItem->getParentReference()?->getId(),
            'listItemUniqueId' => $uniqueId ? strtolower($uniqueId) : null,
            'siteId' => $ids?->getSiteId(),
            'listId' => $ids?->getListId(),
            'path' => $this->folderPath($driveItem->getParentReference()?->getPath()),
            'webUrl' => $driveItem->getWebUrl(),
            // Stored as wall-clock time, so convert before it is compared with the column.
            'modifiedAt' => $modified ? Carbon::instance($modified)->setTimezone(config('app.timezone')) : null,
        ];
    }

    /**
     * The archive drive is the site's whole library; only one folder of it is the document system.
     */
    private function inDiscoveryFolder(?string $path): bool
    {
        $folder = mb_strtolower(trim((string) config('filesystems.sharepoint.document_discovery_folder'), '/'));

        if ($folder === '') {
            return true;
        }

        $path = mb_strtolower((string) $path);

        return $path === $folder || str_starts_with($path, $folder.'/');
    }

    private function isDocumentFile(?string $name): bool
    {
        $extension = mb_strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

        return in_array($extension, config('filesystems.sharepoint.document_discovery_extensions', []), true);
    }

    /**
     * "/drives/b!…/root:/VU SA/Protokolai" → "VU SA/Protokolai"; the root itself → ''.
     */
    private function folderPath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $relative = str_contains($path, 'root:') ? substr($path, strpos($path, 'root:') + 5) : $path;

        return trim(rawurldecode($relative), '/');
    }

    /**
     * Deleted items carry only their drive item id. A full listing also removes whatever
     * the archive no longer contains, which catches deletions older than the delta history.
     *
     * @param  list<string>  $deletedIds  drive item ids
     * @param  list<string>  $listIds  SharePoint lists the listing covered
     * @param  list<string|null>  $seenUniqueIds  lowercased list item ids present in the listing
     * @return Collection<int, Document>
     */
    private function removalCandidates(array $deletedIds, array $listIds, array $seenUniqueIds, bool $isFull): Collection
    {
        $removed = $this->documentsWhereIn('sharepoint_drive_item_id', $deletedIds)
            ->whereNull('removed_from_sharepoint_at');

        // An empty listing must never read as "everything was deleted".
        if (! $isFull || $listIds === []) {
            return $removed;
        }

        $seen = array_flip(array_filter($seenUniqueIds));

        $missing = Document::query()
            ->whereNull('removed_from_sharepoint_at')
            ->whereIn('sharepoint_list_id', $listIds)
            ->get()
            ->reject(fn (Document $document): bool => isset($seen[strtolower($document->sharepoint_id)]));

        return $removed->concat($missing)->unique('id')->values();
    }

    /**
     * The archive holds tens of thousands of files, past MariaDB's 65,535 placeholders per query.
     *
     * @param  array<int, string|null>  $values
     * @return Collection<int, Document>
     */
    private function documentsWhereIn(string $column, array $values): Collection
    {
        return collect($values)->filter()->chunk(1000)
            ->flatMap(fn (Collection $chunk) => Document::query()->whereIn($column, $chunk->values()->all())->get())
            ->values();
    }
}
