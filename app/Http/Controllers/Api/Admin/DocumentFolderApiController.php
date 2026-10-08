<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Documents\UpdateDocumentStatus;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\DestroyRemovedDocumentsRequest;
use App\Http\Requests\IndexDocumentFolderRequest;
use App\Http\Requests\UpdateDocumentStatusRequest;
use App\Http\Resources\DocumentRowResource;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * The managers' document view: every published file, plus every file they may manage, browsed by
 * SharePoint folder or as one list. Rows they may not manage are read-only.
 */
class DocumentFolderApiController extends ApiController
{
    /** Files per request; "Rodyti daugiau" asks for the next ones. */
    private const int PAGE_SIZE = 100;

    public function index(IndexDocumentFolderRequest $request): JsonResponse
    {
        $this->authorizeApi('create', Document::class);

        $user = $request->user();
        $path = $request->hasPath() ? $request->folderPath() : $this->startPath($user);
        $flat = $request->flat();
        $scope = fn () => $this->scope($user, $request->show(), $request->contentType());

        $search = (string) $request->validated('search');
        $files = match (true) {
            // Files gone from SharePoint are a clean-up list for the whole archive; their old folder no longer matters.
            $request->show() === 'removed' => $scope(),
            $flat || filled($search) => $this->underPath($scope(), $path),
            default => $scope()->where(fn (Builder $query) => $path === ''
                ? $query->whereNull('sharepoint_path')->orWhere('sharepoint_path', '')
                : $query->where('sharepoint_path', $path)),
        };

        if (filled($search)) {
            $files->where(fn (Builder $query) => $query->where('title', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }

        // A folder reads like SharePoint, by name; the list puts what changed last first.
        $flat ? $files->orderByDesc('sharepoint_modified_at') : $files->orderBy('name');

        $limit = $request->limit(self::PAGE_SIZE);
        $page = $files
            ->with('institution.tenant:id,shortname')
            ->orderBy('id')
            ->offset($request->offset())
            ->limit($limit + 1)
            ->get();
        $hasMore = $page->count() > $limit;

        return $this->jsonSuccess([
            'path' => $path,
            'breadcrumbs' => $this->breadcrumbs($path),
            // Folders and filters only come with the first page; later pages just add files.
            'folders' => $flat || filled($search) || $request->offset() > 0 ? [] : $this->childFolders($this->underPath($scope(), $path), $path),
            'files' => DocumentRowResource::collection($page->take($limit))->resolve($request),
            'next_offset' => $hasMore ? $request->offset() + $limit : null,
            'content_types' => filled($search) ? [] : $this->contentTypes($user, $path),
        ]);
    }

    /**
     * Forget files that are gone from SharePoint. Nothing else may be deleted: a deleted SharePoint
     * file would only be rediscovered as a new one.
     */
    public function destroy(DestroyRemovedDocumentsRequest $request): JsonResponse
    {
        $documents = $request->documents();
        $documents->each->delete();

        return $this->jsonSuccess(
            ['ids' => $documents->modelKeys()],
            __('messages.document.removed_deleted', ['count' => $documents->count()]),
        );
    }

    /**
     * Show or hide files from the folder view without a page visit, so the folder stays where it is.
     */
    public function updateStatus(UpdateDocumentStatusRequest $request): JsonResponse
    {
        $message = UpdateDocumentStatus::executeWithMessage($request->documents(), $request->status(), $request->user());

        return $this->jsonSuccess(
            DocumentRowResource::collection($request->documents()->load('institution.tenant:id,shortname'))->resolve($request),
            $message,
        );
    }

    /**
     * Try creating a published document's public link again, checking SharePoint from scratch.
     */
    public function refresh(Document $document): JsonResponse
    {
        $this->authorizeApi('update', $document);

        // So the row reads "being created" rather than "failed" until the job reports back.
        $document->update(['sync_status' => 'pending']);
        SyncDocumentFromSharePointJob::dispatch($document, force: true);

        return $this->jsonSuccess(new DocumentRowResource($document->refresh()->load('institution.tenant:id,shortname'))->resolve(request()), __('messages.document.refresh_queued'));
    }

    /**
     * @return Builder<Document>
     */
    private function scope(User $user, string $show = 'all', ?string $contentType = null): Builder
    {
        if ($show === 'removed') {
            return Document::query()
                ->manageableBy($user)
                ->whereNotNull('removed_from_sharepoint_at')
                ->when($contentType, fn (Builder $query, string $type) => $query->where('content_type', $type));
        }

        $status = match ($show) {
            'published' => DocumentStatus::Published,
            'pending' => DocumentStatus::Pending,
            'hidden' => DocumentStatus::Hidden,
            default => null,
        };

        return Document::query()
            ->browsableBy($user)
            ->whereNull('removed_from_sharepoint_at')
            ->when($status, fn (Builder $query, DocumentStatus $status) => $query->where('status', $status))
            ->when($contentType, fn (Builder $query, string $type) => $query->where('content_type', $type));
    }

    /**
     * The content types present below this folder, for its filter.
     *
     * @return list<string>
     */
    private function contentTypes(User $user, string $path): array
    {
        return $this->underPath($this->scope($user), $path)
            ->whereNotNull('content_type')
            ->distinct()
            ->orderBy('content_type')
            ->pluck('content_type')
            ->all();
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    private function underPath(Builder $query, string $path): Builder
    {
        if ($path === '') {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('sharepoint_path', $path)
            ->orWhere('sharepoint_path', 'like', addcslashes($path, '\\%_').'/%'));
    }

    /**
     * Open where the user's files are: the deepest folder holding all they may manage.
     */
    private function startPath(User $user): string
    {
        $paths = Document::query()->manageableBy($user)->whereNull('removed_from_sharepoint_at')
            ->whereNotNull('sharepoint_path')->distinct()->pluck('sharepoint_path');

        if ($paths->isEmpty()) {
            return '';
        }

        $common = explode('/', (string) $paths->first());

        foreach ($paths as $path) {
            $segments = explode('/', (string) $path);
            $length = 0;

            while ($length < count($common) && $length < count($segments) && mb_strtolower($common[$length]) === mb_strtolower($segments[$length])) {
                $length++;
            }

            $common = array_slice($common, 0, $length);
        }

        return implode('/', $common);
    }

    /**
     * @param  Builder<Document>  $query
     * @return list<array{name: string, path: string, counts: array{published: int, pending: int, hidden: int}}>
     */
    private function childFolders(Builder $query, string $path): array
    {
        $prefixLength = $path === '' ? 0 : mb_strlen($path) + 1;
        $folders = [];

        $rows = $query->whereNotNull('sharepoint_path')
            ->toBase()
            ->selectRaw('sharepoint_path, status, count(*) as aggregate')
            ->groupBy('sharepoint_path', 'status')
            ->get();

        foreach ($rows as $row) {
            $remainder = mb_substr((string) $row->sharepoint_path, $prefixLength);

            if ($remainder === '' || ($path !== '' && mb_strtolower((string) $row->sharepoint_path) === mb_strtolower($path))) {
                continue;
            }

            $name = explode('/', $remainder)[0];
            $folders[$name] ??= ['name' => $name, 'path' => ltrim($path.'/'.$name, '/'), 'counts' => ['published' => 0, 'pending' => 0, 'hidden' => 0]];
            $folders[$name]['counts'][$row->status] = ($folders[$name]['counts'][$row->status] ?? 0) + (int) $row->aggregate;
        }

        uksort($folders, strnatcasecmp(...));

        return array_values($folders);
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    private function breadcrumbs(string $path): array
    {
        $crumbs = [];
        $current = '';

        foreach (array_filter(explode('/', $path), fn (string $segment): bool => $segment !== '') as $segment) {
            $current = ltrim($current.'/'.$segment, '/');
            $crumbs[] = ['name' => $segment, 'path' => $current];
        }

        return $crumbs;
    }
}
