<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StagingResourceReadOnlyException;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Files\BulkDeleteFilesRequest;
use App\Http\Requests\Files\CreateDirectoryRequest;
use App\Http\Requests\Files\FilePathRequest;
use App\Http\Requests\Files\IndexFilesRequest;
use App\Http\Requests\Files\UploadImageRequest;
use App\Models\File;
use App\Models\User;
use App\Services\FileStorageService;
use App\Services\FileUsageScanner;
use App\Services\ImageUploadService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\StoragePath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Response as InertiaResponse;
use Intervention\Image\Laravel\Facades\Image;

class FilesController extends AdminController
{
    /** @var list<string> Shared form-image folders use the class-level create ability. */
    private const array SHARED_IMAGE_FOLDERS = [
        'banners',
        'calendar',
        'contacts',
        'institutions',
        'news',
        'pages',
        'resources',
        'uploads',
    ];

    public function __construct(
        public Authorizer $authorizer,
        protected ImageUploadService $imageUploadService,
        protected FileStorageService $fileStorage
    ) {}

    public function index(IndexFilesRequest $request): InertiaResponse|RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path') ?? 'public/files');
        } catch (\InvalidArgumentException) {
            abort(400, 'Invalid path format');
        }

        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
            $fallback = $this->fileStorage->fallbackDirectory($request->user(), $this->authorizer);

            if ($fallback !== null) {
                return $this->redirectResponse('files.index', ['path' => $fallback])
                    ->with('info', __('files.messages.redirected_to_tenant_folder'));
            }

            throw new AuthorizationException(__('files.errors.no_filesystem_access'));
        }

        ['files' => $files, 'directories' => $directories, 'path' => $currentDirectory] = $this->fileStorage->listDirectory($path);

        return $this->inertiaResponse('Admin/Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

    public function createDirectory(CreateDirectoryRequest $request): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['path' => __('files.errors.invalid_directory_path')]);
        }

        $name = trim($request->validated('name'));

        if (! $request->user()->can('createInDirectory', [File::class, $path])) {
            throw new AuthorizationException(__('files.errors.no_create_directory_permission'));
        }

        $newDirectoryPath = $path.'/'.$name;

        if (Storage::exists($newDirectoryPath)) {
            return back()->withErrors(['name' => 'Aplankas su tokiu pavadinimu jau egzistuoja.']);
        }

        try {
            $publicPath = str_replace('public/', '', $newDirectoryPath);

            if (! Storage::disk('public')->makeDirectory($publicPath)) {
                throw new \Exception('Failed to create directory');
            }

            Log::info('Directory created', [
                'path' => $newDirectoryPath,
                'user_id' => $request->user()->id,
                'name' => $name,
            ]);

            return back()->with('success', __('files.messages.directory_created', ['name' => $name]));
        } catch (\Exception $e) {
            Log::error('Error creating directory', [
                'path' => $newDirectoryPath,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'name' => $name,
            ]);

            return back()->withErrors(['error' => __('files.errors.create_directory_failed')]);
        }
    }

    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        $file = $request->file('image') ?? $request->file('file');

        if (! $file instanceof UploadedFile) {
            return response()->json(['error' => __('files.errors.image_missing')], 400);
        }

        $originalName = $file->getClientOriginalName() ?: (string) $request->validated('name');

        if ($originalName === '') {
            return response()->json(['error' => __('files.errors.file_name_missing')], 400);
        }

        $path = (string) $request->validated('path');

        if (StoragePath::hasTraversal($path)) {
            return response()->json(['error' => __('files.errors.invalid_directory_path')], 422);
        }

        try {
            if ($this->isSharedImageFolder($path)) {
                // Form images target shared folders rather than a tenant's file-manager tree.
                if (! $this->mayUploadToSharedFolder($request->user(), $path)) {
                    return response()->json(['error' => __('files.errors.no_upload_permission')], 403);
                }
            } elseif (! $this->isTipTapUpload($path)) {
                if (! $request->user()->can('createInDirectory', [File::class, $this->fileStorage->normalizeFilePath($path)])) {
                    return response()->json(['error' => __('files.errors.no_upload_permission')], 403);
                }
            }

            $directory = $this->resolveUploadDirectory($path, $request->user());
            $result = $this->imageUploadService->processAndSave($file, $directory, $originalName);
        } catch (AuthorizationException|StagingResourceReadOnlyException $e) {
            throw $e;
        } catch (\InvalidArgumentException) {
            return response()->json(['error' => __('files.errors.invalid_directory_path')], 422);
        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'request_data' => $request->only(['name', 'path']),
            ]);

            return response()->json(['error' => __('files.errors.upload_failed')], 500);
        }

        Log::info('Image uploaded via FilesController', [
            'original_name' => $originalName,
            'processed_name' => $result['name'],
            'directory' => $directory,
            'original_size' => $result['originalSize'],
            'compressed_size' => $result['compressedSize'],
            'compression_ratio' => $result['compressionRatio'],
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'url' => $result['url'],
            'name' => $result['name'],
            'originalSize' => $result['originalSize'],
            'compressedSize' => $result['compressedSize'],
            'compressionRatio' => $result['compressionRatio'],
            'message' => ImageUploadService::shortenFilename($originalName).' optimized and converted to WebP',
        ]);
    }

    protected function resolveUploadDirectory(string $path, User $user): string
    {
        if ($this->isTipTapUpload($path)) {
            return $this->resolveTipTapDirectory($user);
        }

        if ($this->isSharedImageFolder($path)) {
            return $path;
        }

        return $this->fileStorage->normalizeFilePath($path);
    }

    protected function resolveTipTapDirectory(User $user): string
    {
        return $this->fileStorage->resolveTipTapDirectory($user, $this->authorizer);
    }

    protected function isTipTapUpload(string $path): bool
    {
        return FileStorageService::isTipTapPath($path);
    }

    protected function isSharedImageFolder(string $path): bool
    {
        return in_array($path, self::SHARED_IMAGE_FOLDERS, true);
    }

    /**
     * Every member uploads their own profile photo to `contacts`, as do duty managers for
     * occupancy photos, so that folder needs no file permission.
     */
    protected function mayUploadToSharedFolder(User $user, string $path): bool
    {
        return $path === 'contacts' || $user->can('create', File::class);
    }

    public function compressImage(FilePathRequest $request): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return back()->withErrors(['error' => __('files.errors.not_compressible')]);
        }

        $directoryPath = dirname($path);
        if (! $request->user()->can('updateInDirectory', [File::class, $directoryPath])) {
            throw new AuthorizationException(__('files.errors.no_modify_permission'));
        }

        if (! \Storage::exists($path) || \Storage::directoryExists($path)) {
            return back()->withErrors(['error' => __('files.errors.file_not_found')]);
        }

        try {
            $fullLocalPath = storage_path('app/'.$path);
            $originalSize = filesize($fullLocalPath) ?: 0;

            $image = Image::decodePath($fullLocalPath);
            $image->scaleDown(width: 1600);
            $quality = $originalSize > 2 * 1024 * 1024 ? 72 : 78; // 2MB threshold

            // save() auto-detects format from file extension
            $image->save($fullLocalPath, quality: $quality);
            clearstatcache();
            $newSize = filesize($fullLocalPath) ?: $originalSize;
            $saved = $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100) : 0;

            Log::info('Image compressed', [
                'path' => $path,
                'converted_to_webp' => false,
                'original_size' => $originalSize,
                'new_size' => $newSize,
                'percent_saved' => $saved,
                'user_id' => $request->user()->id,
            ]);

            $fileName = basename($path);
            $msg = __('files.messages.image_optimised', ['percent' => $saved]);

            return back()->with('success', $fileName.' – '.$msg)->with('data', [
                'path' => $path,
                'percent_saved' => $saved,
                'new_size' => $newSize,
                'converted' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Image compression failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => __('files.errors.compress_failed', ['error' => $e->getMessage()])]);
        }
    }

    public function delete(FilePathRequest $request): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        $directoryPath = dirname($path);
        if (! $request->user()->can('deleteInDirectory', [File::class, $directoryPath])) {
            throw new AuthorizationException(__('files.errors.no_delete_permission'));
        }

        if (! Storage::exists($path)) {
            return back()->withErrors(['file' => __('files.errors.file_not_found')]);
        }

        if (Storage::directoryExists($path)) {
            return back()->withErrors(['file' => __('files.errors.cannot_delete_directory_this_way')]);
        }

        $fileName = basename($path);

        try {
            if (! Storage::delete($path)) {
                throw new \Exception('Failed to delete file');
            }

            Log::info('File deleted', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'file_name' => $fileName,
            ]);

            return back()->with('success', __('files.messages.file_deleted', ['name' => $fileName]));
        } catch (\Exception $e) {
            Log::error('Error deleting file', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'file_name' => $fileName,
            ]);

            return back()->withErrors(['error' => __('files.errors.delete_failed')]);
        }
    }

    public function bulkDelete(BulkDeleteFilesRequest $request): RedirectResponse
    {
        $paths = $request->validated('paths');
        $deletedCount = 0;
        $errors = [];
        $skippedCount = 0;
        $authorizedPaths = [];

        foreach ($paths as $path) {
            try {
                $validatedPath = $this->fileStorage->normalizeFilePath($path);
            } catch (\InvalidArgumentException) {
                $errors[] = __('files.errors.bulk_invalid_path', ['name' => basename($path)]);
                $skippedCount++;

                continue;
            }

            if (! $request->user()->can('deleteInDirectory', [File::class, dirname($validatedPath)])) {
                throw new AuthorizationException(__('files.errors.bulk_no_delete_permission', ['name' => basename($path)]));
            }

            $authorizedPaths[] = $validatedPath;
        }

        foreach ($authorizedPaths as $validatedPath) {
            try {
                if (! Storage::exists($validatedPath)) {
                    $errors[] = __('files.errors.bulk_file_not_found', ['name' => basename($validatedPath)]);
                    $skippedCount++;

                    continue;
                }

                if (Storage::directoryExists($validatedPath)) {
                    $errors[] = __('files.errors.bulk_is_directory', ['name' => basename($validatedPath)]);
                    $skippedCount++;

                    continue;
                }

                if (! Storage::delete($validatedPath)) {
                    throw new \Exception('Failed to delete file');
                }

                $deletedCount++;

                Log::info('Bulk file deleted', [
                    'path' => $validatedPath,
                    'user_id' => $request->user()->id,
                    'file_name' => basename($validatedPath),
                ]);

            } catch (\Exception $e) {
                $errors[] = __('files.errors.bulk_delete_error', ['name' => basename($validatedPath)]);
                $skippedCount++;
                Log::error('Bulk delete error', [
                    'path' => $validatedPath,
                    'user_id' => $request->user()->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($deletedCount > 0 && $skippedCount === 0) {
            return back()->with('success', trans_choice('files.messages.bulk_deleted', $deletedCount, ['count' => $deletedCount]));
        } elseif ($deletedCount > 0) {
            $message = trans_choice('files.messages.bulk_deleted_partial', $deletedCount, ['count' => $deletedCount]);
            if (! empty($errors)) {
                $message .= '. '.__('files.messages.bulk_skipped', [
                    'count' => $skippedCount,
                    'files' => implode(', ', array_slice($errors, 0, 3)),
                ]);
                if (count($errors) > 3) {
                    $message .= ' '.__('files.messages.and_more', ['count' => count($errors) - 3]);
                }
            }

            return back()->with('warning', $message);
        } else {
            $errorMessage = __('files.errors.bulk_delete_all_failed');
            if (! empty($errors)) {
                $errorMessage .= ' '.__('files.messages.bulk_errors', ['files' => implode(', ', array_slice($errors, 0, 3))]);
                if (count($errors) > 3) {
                    $errorMessage .= ' '.__('files.messages.and_more', ['count' => count($errors) - 3]);
                }
            }

            return back()->withErrors(['error' => $errorMessage]);
        }
    }

    public function deleteDirectory(FilePathRequest $request): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_folder_path')]);
        }

        if ($path === 'public/files') {
            return back()->withErrors(['error' => __('files.errors.cannot_delete_root')]);
        }

        $parentDirectory = dirname($path);
        if (! $request->user()->can('deleteDirectory', [File::class, $parentDirectory])) {
            throw new AuthorizationException(__('files.errors.no_directory_delete_permission'));
        }

        if (! Storage::directoryExists($path)) {
            return back()->withErrors(['directory' => __('files.errors.directory_not_found')]);
        }

        $files = Storage::files($path);
        $subdirectories = Storage::directories($path);

        if (count($files) > 0 || count($subdirectories) > 0) {
            return back()->withErrors(['directory' => __('files.errors.directory_not_empty')]);
        }

        $directoryName = basename($path);

        try {
            $publicPath = str_replace('public/', '', $path);

            if (! Storage::disk('public')->deleteDirectory($publicPath)) {
                throw new \Exception('Failed to delete directory');
            }

            Log::info('Directory deleted', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'directory_name' => $directoryName,
            ]);

            return back();
        } catch (\Exception $e) {
            Log::error('Error deleting directory', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'directory_name' => $directoryName,
            ]);

            return back()->withErrors(['error' => __('files.errors.delete_directory_failed')]);
        }
    }

    public function scanFileUsage(FilePathRequest $request, FileUsageScanner $scanner): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            throw new AuthorizationException(__('files.errors.no_scan_permission'));
        }

        if (! Storage::exists($path)) {
            return back()->withErrors(['error' => __('files.errors.file_not_found')]);
        }

        try {
            $usageData = $scanner->scanFileUsage($path);

            Log::info('File usage scanned', [
                'file_path' => $path,
                'total_usages' => $usageData['total_usages'],
                'is_safe_to_delete' => $usageData['is_safe_to_delete'],
                'user_id' => $request->user()->id,
            ]);

            if ($usageData['is_safe_to_delete']) {
                $message = __('files.messages.usage_safe', ['count' => count($usageData['scanned_models'])]);

                return back()->with('data', $usageData)->with('success', $message);
            } else {
                $message = __('files.messages.usage_found', ['count' => $usageData['total_usages']]);

                return back()->with('data', $usageData)->with('info', $message);
            }
        } catch (\Exception $e) {
            Log::error('File usage scan failed', [
                'file_path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => __('files.errors.scan_failed', ['error' => $e->getMessage()])]);
        }
    }
}
