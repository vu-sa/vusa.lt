<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        } catch (\InvalidArgumentException $e) {
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
                    ], 'Nukreiptas į jūsų padalinio failų aplanką.');
                }
            }

            return $this->jsonError('Neturite teisių peržiūrėti šio aplanko.', 403, code: 'INSUFFICIENT_PERMISSIONS');
        }

        [$files, $directories] = $this->getFilesFromStorage($path, $extensions);

        return $this->jsonSuccess([
            'files' => $files,
            'directories' => $directories,
            'path' => $path,
        ]);
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
        $directories = collect(Storage::directories($path))->map(function ($dir) {
            return [
                'path' => $dir,
                'name' => basename($dir),
                'type' => 'directory',
            ];
        })->toArray();

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
