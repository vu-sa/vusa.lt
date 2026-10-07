<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Files\IndexFilesRequest;
use App\Http\Requests\Files\SearchFilesRequest;
use App\Http\Requests\Files\ThumbnailFileRequest;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\FileStorageService;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
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

    public function index(IndexFilesRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        $requestedPath = $request->validated('path') ?? 'public/files';

        try {
            $path = $this->fileStorage->normalizeFilePath($requestedPath);
        } catch (\InvalidArgumentException) {
            return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
        }

        // Clients browse paths this endpoint returned, so a spelling normalisation rewrites is crafted.
        if ($requestedPath !== $path) {
            return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
        }

        $extensions = $this->requestedExtensions($request);

        if (! $user->can('viewDirectory', [File::class, $path])) {
            // Only the root falls back; a deep link to a forbidden folder stays an explicit 403.
            $fallback = $path === 'public/files' ? $this->fileStorage->fallbackDirectory($user, $this->authorizer) : null;

            if ($fallback !== null) {
                ['files' => $files, 'directories' => $directories] = $this->fileStorage->listDirectory($fallback, $extensions);

                return $this->jsonSuccess([
                    'files' => $files,
                    'directories' => $directories,
                    'path' => $fallback,
                    'redirected' => true,
                ], __('files.messages.redirected_to_tenant_folder'));
            }

            return $this->jsonError(__('files.errors.no_directory_access'), 403, code: 'INSUFFICIENT_PERMISSIONS');
        }

        ['files' => $files, 'directories' => $directories] = $this->fileStorage->listDirectory($path, $extensions);

        return $this->jsonSuccess([
            'files' => $files,
            'directories' => $directories,
            'path' => $path,
        ]);
    }

    /** @var list<int> Prevents one cached derivative per user-supplied pixel width. */
    private const array THUMBNAIL_WIDTHS = [160, 320, 640];

    /** @var list<string> SVG cannot be rasterised; other formats use the original. */
    private const array THUMBNAILABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function thumbnail(ThumbnailFileRequest $request): Response
    {
        $user = $this->requireAuth($request);

        $requestedPath = (string) $request->validated('path');

        try {
            $path = $this->fileStorage->normalizeFilePath($requestedPath);
        } catch (\InvalidArgumentException) {
            abort(400, 'Invalid path format');
        }

        // Thumbnail URLs are built from listed paths, so a spelling normalisation rewrites is crafted.
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

        $width = (int) ($request->validated('w') ?? 320);

        if (! in_array($width, self::THUMBNAIL_WIDTHS, true)) {
            $width = 320;
        }

        return $this->respondWithThumbnail($path, $width);
    }

    private function respondWithThumbnail(string $path, int $width): BinaryFileResponse
    {
        // A replaced original must invalidate its cached thumbnail.
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

    // A single batch avoids competing uploads interrupting each other in Inertia's visit stream.
    public function store(StoreFilesRequest $request): JsonResponse
    {
        $this->requireAuth($request);

        try {
            $path = $request->uploadDirectory($this->fileStorage, $this->authorizer);
        } catch (\InvalidArgumentException) {
            return $this->jsonError(__('files.errors.invalid_directory_path'), 400, code: 'INVALID_PATH');
        } catch (AuthorizationException) {
            return $this->jsonError(__('files.errors.no_upload_permission'), 403, code: 'INSUFFICIENT_PERMISSIONS');
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

    // Limits bound filesystem work per keystroke; meta reports truncated results.
    private const int SEARCH_MAX_DEPTH = 6;

    private const int SEARCH_MAX_DIRECTORIES = 400;

    private const int SEARCH_MAX_RESULTS = 100;

    // Each descendant needs authorization to avoid exposing another tenant's filenames.
    public function search(SearchFilesRequest $request): JsonResponse
    {
        $user = $this->requireAuth($request);

        try {
            $root = $this->fileStorage->normalizeFilePath((string) ($request->validated('path') ?: 'public/files'));
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

    /** @return list<string>|null */
    private function requestedExtensions(FormRequest $request): ?array
    {
        if (! $request->validated('extensions')) {
            return null;
        }

        $requested = array_map(
            fn (string $ext) => strtolower(trim($ext)),
            explode(',', (string) $request->validated('extensions'))
        );

        return array_values(array_intersect($requested, StoreFilesRequest::getAllowedExtensions()));
    }

    public function allowedTypes(Request $request): JsonResponse
    {
        $this->requireAuth($request);

        $extensions = StoreFilesRequest::getAllowedExtensions();

        return $this->jsonSuccess([
            'extensions' => $extensions,
            'accept' => '.'.implode(',.', $extensions),
            'maxSizeMB' => StoreFilesRequest::MAX_SIZE_KB / 1024,
        ]);
    }
}
