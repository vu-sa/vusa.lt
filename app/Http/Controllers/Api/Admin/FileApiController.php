<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class FileApiController extends ApiController
{
    public function __construct(
        public Authorizer $authorizer
    ) {}

    /**
     * Safely validate and normalize file path
     */
    protected function validateAndNormalizePath(string $path): string
    {
        // Remove any path traversal attempts
        $path = str_replace(['../', '..\\', '../', '..\\'], '', $path);

        // If user supplied only a filename or relative fragment, prepend base directory
        if (! str_starts_with($path, 'public/files')) {
            $path = ltrim($path, '/');
            $path = str_contains($path, '/')
                ? 'public/files/'.$path
                : 'public/files/'.$path;
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
        $extensions = null;
        if ($request->filled('extensions')) {
            $requested = array_map(
                fn (string $ext) => strtolower(trim($ext)),
                explode(',', (string) $request->input('extensions'))
            );
            $extensions = array_values(array_intersect($requested, StoreFilesRequest::getAllowedExtensions()));
        }

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

        try {
            $path = $this->validateAndNormalizePath((string) $request->input('path', ''));
        } catch (\InvalidArgumentException) {
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
        $directories = collect(Storage::directories($path))->map(fn ($dir) => [
            'path' => $dir,
            'name' => basename($dir),
            'type' => 'directory',
        ])->toArray();

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
            ->values()
            ->toArray();

        return [$files, $directories];
    }
}
