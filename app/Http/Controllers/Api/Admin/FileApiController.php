<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Files\SearchFilesRequest;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\FileStorageService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\StoragePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class FileApiController extends ApiController
{
    public function __construct(
        public Authorizer $authorizer,
        protected FileStorageService $fileStorage
    ) {}

    /**
     * Safely validate and normalize file path
     */
    protected function validateAndNormalizePath(string $path): string
    {
        // Drop every traversal segment. Doing this per-segment rather than by str_replace is
        // what makes `....//` and `..././` safe — see App\Support\StoragePath.
        $path = StoragePath::normalizeRelative($path);

        // If user supplied only a filename or relative fragment, prepend base directory
        if (! str_starts_with($path, 'public/files')) {
            $path = 'public/files/'.ltrim($path, '/');
        }

        // Normalize path separators and remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);
        $path = rtrim($path, '/');

        // Security: allow Unicode letters, marks, numbers, underscores, hyphens, dots, spaces, parentheses, and forward slashes
        if (! preg_match('/^[\p{L}\p{M}\p{N}\/_. ()-]+$/u', $path)) {
            throw new \InvalidArgumentException('Invalid path format');
        }

        return $path;
    }

    /**
     * Get files and directories from storage path.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        try {
            $requestedPath = $request->input('path', 'public/files');
            $path = $this->validateAndNormalizePath($requestedPath);
        } catch (\InvalidArgumentException) {
            return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
        }

        // Optional server-side extension filter (e.g. the image picker only wants
        // jpg/png/webp/...). Validated against the same allowlist used for uploads so
        // an unrecognised value can't silently match nothing or leak into a query.
        $extensions = $this->requestedExtensions($request);

        // If normalization changed the path (e.g., traversal attempts), treat as invalid
        if ($requestedPath !== $path) {
            return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
        }

        // Check if user can view this specific directory
        if (! $user->can('viewDirectory', [File::class, $path])) {
            // Try to redirect to user's allowed directory for root requests
            if (in_array($requestedPath, [null, '', 'public/files'], true) && $this->authorizer->getTenants()->count() > 0) {
                $allowedPath = 'public/files/padaliniai/vusa'.($this->authorizer->getTenants()->first()->alias ?? '');

                if ($user->can('viewDirectory', [File::class, $allowedPath])) {
                    [$files, $directories] = $this->getFilesFromStorage($allowedPath, $extensions);

                    return $this->jsonSuccess([
                        'files' => $files,
                        'directories' => $directories,
                        'path' => $allowedPath,
                        'redirected' => true,
                    ], __('files.messages.redirected_to_tenant_folder'));
                }
            }

            return $this->jsonError(__('files.errors.no_directory_access'), 403, code: 'INSUFFICIENT_PERMISSIONS');
        }

        [$files, $directories] = $this->getFilesFromStorage($path, $extensions);

        return $this->jsonSuccess([
            'files' => $files,
            'directories' => $directories,
            'path' => $path,
        ]);
    }

    /**
     * Widths the grid and its hover preview ask for. An allowlist keeps a crafted
     * `w` from filling the cache disk with one derivative per pixel value.
     *
     * @var array<int, int>
     */
    private const array THUMBNAIL_WIDTHS = [160, 320, 640];

    /**
     * Extensions worth rasterising. SVG is left alone (already small, and the driver
     * cannot rasterise it); the grid falls back to the original for anything else.
     *
     * @var array<int, string>
     */
    private const array THUMBNAILABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Serve a cached, downscaled copy of a stored image.
     *
     * The file grid used to point every tile at the full-resolution original, so
     * opening a folder of 50 photos downloaded and decoded hundreds of megabytes.
     */
    public function thumbnail(Request $request): Response
    {
        $user = $this->requireAuth($request);

        $requestedPath = (string) $request->input('path', '');

        try {
            $path = $this->validateAndNormalizePath($requestedPath);
        } catch (\InvalidArgumentException) {
            abort(400, 'Invalid path format');
        }

        // index() refuses anything normalisation had to rewrite; without the same guard here a
        // traversal spelling would only ever be stopped by the policy on dirname($path).
        if ($requestedPath !== $path) {
            abort(400, 'Invalid path format');
        }

        if (! $user->can('viewDirectory', [File::class, dirname($path)])) {
            abort(403, __('files.errors.no_directory_access'));
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, self::THUMBNAILABLE_EXTENSIONS, true) || ! Storage::exists($path)) {
            abort(404);
        }

        $width = (int) $request->input('w', 320);

        if (! in_array($width, self::THUMBNAIL_WIDTHS, true)) {
            $width = 320;
        }

        return $this->respondWithThumbnail($path, $width);
    }

    /**
     * Build the derivative once and serve it from disk on every later request.
     *
     * The source's modified time is part of the cache key, so re-uploading over a
     * filename produces a new thumbnail instead of serving the stale one.
     */
    private function respondWithThumbnail(string $path, int $width): BinaryFileResponse
    {
        $cacheKey = hash('xxh128', $path.'|'.Storage::lastModified($path).'|'.$width);
        $cachePath = Storage::path('thumbnails/'.$cacheKey.'.webp');

        if (! is_file($cachePath)) {
            Storage::makeDirectory('thumbnails');

            Image::decodePath(Storage::path($path))
                ->scaleDown(width: $width)
                ->encodeUsingFileExtension('webp', quality: 75)
                ->save($cachePath);
        }

        return response()
            ->file($cachePath, ['Content-Type' => 'image/webp'])
            ->setMaxAge(31536000)
            ->setPrivate();
    }

    /**
     * Upload a batch of files into one directory.
     *
     * The whole batch travels in a single request on purpose. The previous flow issued one
     * Inertia visit per file group, and Inertia's sync stream is single-slot and interruptible —
     * the second visit cancelled the first, which then fired neither onSuccess nor onError and
     * left the caller's spinner running over an upload that had in fact succeeded.
     */
    public function store(StoreFilesRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        $requestedPath = (string) $request->validated('path');

        if (FileStorageService::isTipTapPath($requestedPath)) {
            // The editor names a `content/` bucket, not a location: the destination comes from
            // the user's own tenant, so there is no request-supplied directory to authorize.
            $path = $this->fileStorage->resolveTipTapDirectory($user, $this->authorizer);
        } else {
            try {
                $path = $this->validateAndNormalizePath($requestedPath);
            } catch (\InvalidArgumentException) {
                return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
            }

            if (! $user->can('viewDirectory', [File::class, $path])) {
                return $this->jsonError(__('files.errors.no_upload_permission'), 403, code: 'INSUFFICIENT_PERMISSIONS');
            }
        }

        $files = array_map(
            fn (array $container) => $container['file'],
            $request->validated('files')
        );

        ['uploaded' => $uploaded, 'failed' => $failed] = $this->fileStorage->storeMany($files, $path);

        if ($uploaded === []) {
            return $this->jsonError(__('files.errors.upload_all_failed'), 422, errors: ['files' => array_column($failed, 'reason')]);
        }

        return $this->jsonSuccess(
            ['uploaded' => $uploaded, 'failed' => $failed, 'path' => $path],
            $this->uploadSummary($uploaded, $failed),
        );
    }

    /**
     * @param  list<array{name: string, path: string, url: string, renamed: bool}>  $uploaded
     * @param  list<array{name: string, reason: string}>  $failed
     */
    private function uploadSummary(array $uploaded, array $failed): string
    {
        $renamed = count(array_filter($uploaded, fn (array $file) => $file['renamed']));
        $stored = count($uploaded) - $renamed;

        $messages = [];

        if ($stored > 0) {
            $messages[] = trans_choice('files.messages.uploaded_count', $stored, ['count' => $stored]);
        }

        if ($renamed > 0) {
            $messages[] = trans_choice('files.messages.renamed_count', $renamed, ['count' => $renamed]);
        }

        $summary = implode(', ', $messages).'.';

        if ($failed !== []) {
            $summary .= ' '.__('files.messages.upload_failed_list', [
                'files' => implode(', ', array_slice(array_column($failed, 'name'), 0, 3)),
            ]);

            if (count($failed) > 3) {
                $summary .= ' '.__('files.messages.and_more', ['count' => count($failed) - 3]);
            }
        }

        return $summary;
    }

    /**
     * How far the recursive search may walk before giving up.
     *
     * The manager's root holds ~50 directories and ~630 loose files, so an uncapped walk would
     * turn one keystroke into a full filesystem traversal. These bounds keep a miss cheap; a
     * truncated result says so in `meta` rather than pretending it is complete.
     */
    private const int SEARCH_MAX_DEPTH = 6;

    private const int SEARCH_MAX_DIRECTORIES = 400;

    private const int SEARCH_MAX_RESULTS = 100;

    /**
     * Search file names recursively beneath a directory.
     *
     * Every directory the walk enters is authorized separately. FilePolicy derives the tenant
     * from the `padaliniai/` path segment alone, so a walk that only checked its starting point
     * would happily report another tenant's filenames.
     */
    public function search(SearchFilesRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        try {
            $root = $this->validateAndNormalizePath((string) ($request->validated('path') ?: 'public/files'));
        } catch (\InvalidArgumentException) {
            return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
        }

        if (! $user->can('viewDirectory', [File::class, $root])) {
            return $this->jsonError(__('files.errors.no_directory_access'), 403, code: 'INSUFFICIENT_PERMISSIONS');
        }

        $extensions = $this->requestedExtensions($request);
        $needle = mb_strtolower($request->searchTerm());

        $results = [];
        $queue = [[$root, 0]];
        $visited = 0;
        $truncated = false;

        while ($queue !== []) {
            [$directory, $depth] = array_shift($queue);

            if (++$visited > self::SEARCH_MAX_DIRECTORIES) {
                $truncated = true;
                break;
            }

            foreach (Storage::files($directory) as $file) {
                $name = basename($file);

                if (! str_contains(mb_strtolower($name), $needle)) {
                    continue;
                }

                if ($extensions !== null && ! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true)) {
                    continue;
                }

                if (count($results) >= self::SEARCH_MAX_RESULTS) {
                    $truncated = true;
                    break 2;
                }

                $results[] = [
                    'path' => $file,
                    'name' => $name,
                    'type' => 'file',
                    'size' => Storage::size($file),
                    'modified' => Storage::lastModified($file),
                    'directory' => dirname($file),
                ];
            }

            if ($depth >= self::SEARCH_MAX_DEPTH) {
                continue;
            }

            foreach (Storage::directories($directory) as $subdirectory) {
                if ($user->can('viewDirectory', [File::class, $subdirectory])) {
                    $queue[] = [$subdirectory, $depth + 1];
                }
            }
        }

        usort($results, fn (array $a, array $b) => strnatcasecmp($a['name'], $b['name']));

        return $this->jsonSuccess(
            ['files' => $results, 'path' => $root],
            meta: ['truncated' => $truncated, 'total' => count($results)],
        );
    }

    /**
     * Validate a comma-separated `extensions` filter against the upload allowlist, so an
     * unrecognised value cannot silently match nothing or leak into a query.
     *
     * @return list<string>|null
     */
    private function requestedExtensions(Request $request): ?array
    {
        if (! $request->filled('extensions')) {
            return null;
        }

        $requested = array_map(
            fn (string $ext) => strtolower(trim($ext)),
            explode(',', (string) $request->input('extensions'))
        );

        return array_values(array_intersect($requested, StoreFilesRequest::getAllowedExtensions()));
    }

    /**
     * Get allowed file types for upload.
     */
    public function allowedTypes(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        // Import the request class for extensions
        $extensions = StoreFilesRequest::getAllowedExtensions();

        return $this->jsonSuccess([
            'extensions' => $extensions,
            'accept' => '.'.implode(',.', $extensions),
            'maxSizeMB' => 50,
        ]);
    }

    /**
     * Get files and directories from storage.
     *
     * @param  array<int, string>|null  $extensions  Lowercase extensions (no dot) to
     *                                               restrict the file listing to, e.g.
     *                                               the image picker asking for only
     *                                               jpg/png/webp/... — directories are
     *                                               never filtered, since users still
     *                                               need to navigate through them.
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function getFilesFromStorage(string $path, ?array $extensions = null): array
    {
        // Storage returns whatever order the filesystem hands back, which reads as random in a
        // directory of this size. Natural, case-insensitive sort so "2. x" precedes "10. x".
        $directories = collect(Storage::directories($path))
            ->map(fn ($dir) => [
                'path' => $dir,
                'name' => basename($dir),
                'type' => 'directory',
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->toArray();

        $files = collect(Storage::files($path))
            ->filter(function ($file) use ($extensions) {
                if ($extensions === null) {
                    return true;
                }

                return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true);
            })
            ->map(function ($file) use ($path) {
                $relativePath = str_replace('public/', '', $file);

                return [
                    'path' => $file,
                    'name' => basename($file),
                    'type' => 'file',
                    'size' => Storage::size($file),
                    'modified' => Storage::lastModified($file),
                    'mimeType' => Storage::mimeType($file),
                    'url' => $path.'/'.$relativePath,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->toArray();

        return [$files, $directories];
    }
}
