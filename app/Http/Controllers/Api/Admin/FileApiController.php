<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
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

        // If normalization changed the path (e.g., traversal attempts), treat as invalid
        if ($requestedPath !== $path) {
            return $this->jsonError('Invalid path format', 400, code: 'INVALID_PATH');
        }

        // Check if user can view this specific directory
        if (! $user->can('viewDirectory', [File::class, $path])) {
            // Try to redirect to user's allowed directory for root requests
            if (in_array($requestedPath, [null, '', 'public/files'], true) && $this->authorizer->getTenants()->count() > 0) {
                $allowedPath = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;

                if ($user->can('viewDirectory', [File::class, $allowedPath])) {
                    [$files, $directories] = $this->getFilesFromStorage($allowedPath);

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

        [$files, $directories] = $this->getFilesFromStorage($path);

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
        $extensions = \App\Http\Requests\StoreFilesRequest::getAllowedExtensions();

        return $this->jsonSuccess([
            'extensions' => $extensions,
            'accept' => '.'.implode(',.', $extensions),
            'maxSizeMB' => 50,
        ]);
    }

    /**
     * Get files and directories from storage.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function getFilesFromStorage(string $path): array
    {
        $directories = collect(Storage::directories($path))->map(function ($dir) {
            return [
                'path' => $dir,
                'name' => basename($dir),
                'type' => 'directory',
            ];
        })->toArray();

        $files = collect(Storage::files($path))->map(function ($file) use ($path) {
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
        })->toArray();

        return [$files, $directories];
    }
}
