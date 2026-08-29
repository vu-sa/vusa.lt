<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Files\BulkDeleteFilesRequest;
use App\Http\Requests\Files\CreateDirectoryRequest;
use App\Http\Requests\Files\FilePathRequest;
use App\Http\Requests\Files\UploadImageRequest;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\FileStorageService;
use App\Services\FileUsageScanner;
use App\Services\ImageUploadService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\StoragePath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class FilesController extends AdminController
{
    /**
     * Folders the admin `<ImageUpload>` component may target directly, matching the `folder`
     * prop values used across resources/js/Components/AdminForms. These are shared across
     * tenants, so uploads into them are gated on the File create ability rather than on a
     * per-directory policy.
     *
     * @var list<string>
     */
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
        protected ImageUploadService $imageUploadService
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
            $path = 'public/files/'.$path;
        }

        // Normalize path separators and remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);
        $path = rtrim($path, '/');

        if (! StoragePath::isSafeRelative($path)) {
            throw new \InvalidArgumentException('Invalid path format');
        }

        return $path;
    }

    protected function getFilesFromStorage($path)
    {
        $path = $this->validateAndNormalizePath($path);

        $directories = collect(Storage::directories($path))->map(fn ($dir) => [
            'path' => $dir,
            'name' => basename($dir),
            'type' => 'directory',
        ])->toArray();

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

        return [
            $files,
            $directories,
            $path,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $path = $this->validateAndNormalizePath($request->path ?? 'public/files');
        } catch (\InvalidArgumentException) {
            abort(400, 'Invalid path format');
        }

        // Check if user can view this specific directory
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
            // Try to redirect to user's allowed directory
            if ($this->authorizer->getTenants()->count() > 0) {
                $allowedPath = 'public/files/padaliniai/vusa'.($this->authorizer->getTenants()->first()->alias ?? '');

                // Check if user can access their tenant directory
                if ($request->user()->can('viewDirectory', [File::class, $allowedPath])) {
                    return $this->redirectResponse('files.index', ['path' => $allowedPath])
                        ->with('info', __('files.messages.redirected_to_tenant_folder'));
                }
            }

            // If no access to tenant directory, redirect to dashboard
            return $this->redirectResponse('dashboard')->with('error', __('files.errors.no_filesystem_access'));
        }

        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

        return $this->inertiaResponse('Admin/Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

    public function getFiles(Request $request)
    {
        try {
            $requestedPath = $request->path ?? 'public/files';
            $path = $this->validateAndNormalizePath($requestedPath);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid path format'], 400);
        }

        // If normalization changed the path (e.g., traversal attempts), treat as invalid input
        if ($requestedPath !== $path) {
            return response()->json(['error' => 'Invalid path format'], 400);
        }

        // Check if user can view this specific directory
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
            // Mirror index() behaviour but only for root directory requests
            if (in_array($requestedPath, [null, '', 'public/files'], true) && $this->authorizer->getTenants()->count() > 0) {
                $allowedPath = 'public/files/padaliniai/vusa'.($this->authorizer->getTenants()->first()->alias ?? '');

                if ($request->user()->can('viewDirectory', [File::class, $allowedPath])) {
                    try {
                        // Set a flash for Inertia toasts even though this is a JSON request.
                        // The frontend triggers a small Inertia reload to pick it up.
                        session()->flash('success', __('files.messages.redirected_to_tenant_folder'));
                        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($allowedPath);

                        return response()->json([
                            'files' => $files,
                            'directories' => $directories,
                            'path' => $currentDirectory,
                            'success' => true,
                            'redirected' => true,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error fetching files after fallback', [
                            'requested_path' => $path,
                            'fallback_path' => $allowedPath,
                            'user_id' => $request->user()->id,
                            'error' => $e->getMessage(),
                        ]);

                        return response()->json([
                            'error' => __('files.errors.fetch_failed_after_redirect'),
                            'code' => 'FETCH_ERROR',
                        ], 500);
                    }
                }
            }

            return response()->json([
                'error' => __('files.errors.no_directory_access'),
                'code' => 'INSUFFICIENT_PERMISSIONS',
            ], 403);
        }

        try {
            [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

            return response()->json([
                'files' => $files,
                'directories' => $directories,
                'path' => $currentDirectory,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching files', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('files.errors.fetch_failed'),
                'code' => 'FETCH_ERROR',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFilesRequest $request)
    {
        $validated = $request->validated();

        $files = $validated['files'];
        $path = (string) $validated['path'];

        // Determine if this is a TipTap upload (content folder) or FileManager upload (custom path)
        $isTipTapUpload = str_starts_with($path, 'content/');

        if ($isTipTapUpload) {
            // resolveTipTapDirectory() now returns a `public/`-prefixed path. storeAs() writes to
            // the default (local) disk, so without that prefix the upload landed in
            // storage/app/files/content/... and the /uploads/... URL the editor inserts 404'd.
            $path = $this->resolveTipTapDirectory($request->user());
        } else {
            // FileManager uploads: validate path normally
            try {
                $path = $this->validateAndNormalizePath($path);
            } catch (\InvalidArgumentException) {
                return back()->withErrors(['path' => 'Neteisingas katalogo kelias.']);
            }

            // Check if user has permission to upload to this directory
            if (! $request->user()->can('viewDirectory', [File::class, $path])) {
                return back()->withErrors(['permission' => __('files.errors.no_upload_permission')]);
            }
        }

        $uploadedCount = 0;
        $renamedCount = 0;
        $errors = [];

        foreach ($files as $fileContainer) {
            $file = $fileContainer['file'];
            $originalName = $file->getClientOriginalName();

            try {
                if (Storage::exists($path.'/'.$originalName)) {
                    // File already exists, add timestamp
                    $timestamp = time();
                    $extension = $file->getClientOriginalExtension();
                    $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
                    $newName = $nameWithoutExtension.'_'.$timestamp.'.'.$extension;

                    $file->storeAs($path, $newName);
                    $renamedCount++;

                    Log::info('File uploaded with new name', [
                        'original_name' => $originalName,
                        'new_name' => $newName,
                        'path' => $path,
                        'user_id' => $request->user()->id,
                    ]);
                } else {
                    $file->storeAs($path, $originalName);
                    $uploadedCount++;

                    Log::info('File uploaded', [
                        'file_name' => $originalName,
                        'path' => $path,
                        'user_id' => $request->user()->id,
                    ]);
                }
            } catch (\Exception $e) {
                $errors[] = $originalName;
                Log::error('File upload error', [
                    'file_name' => $originalName,
                    'path' => $path,
                    'user_id' => $request->user()->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create success message
        $messages = [];
        if ($uploadedCount > 0) {
            $messages[] = trans_choice('files.messages.uploaded_count', $uploadedCount, ['count' => $uploadedCount]);
        }
        if ($renamedCount > 0) {
            $messages[] = trans_choice('files.messages.renamed_count', $renamedCount, ['count' => $renamedCount]);
        }

        if (! empty($messages)) {
            $successMessage = implode(', ', $messages).'.';
            if (! empty($errors)) {
                $successMessage .= ' '.__('files.messages.upload_failed_list', ['files' => implode(', ', array_slice($errors, 0, 3))]);
                if (count($errors) > 3) {
                    $successMessage .= ' '.__('files.messages.and_more', ['count' => count($errors) - 3]);
                }

                return back()->with('warning', $successMessage);
            }

            return back()->with('success', $successMessage);
        } else {
            return back()->withErrors(['error' => __('files.errors.upload_all_failed')]);
        }
    }

    public function createDirectory(CreateDirectoryRequest $request)
    {
        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['path' => 'Neteisingas katalogo kelias.']);
        }

        $name = trim($request->input('name'));

        // Check if user has permission to create directories in this path
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
            return back()->withErrors(['permission' => __('files.errors.no_create_directory_permission')]);
        }

        $newDirectoryPath = $path.'/'.$name;

        // Check if directory already exists
        if (Storage::exists($newDirectoryPath)) {
            return back()->withErrors(['name' => 'Aplankas su tokiu pavadinimu jau egzistuoja.']);
        }

        try {
            // Remove 'public/' from the start for Storage::disk('public')
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

    public function uploadImage(UploadImageRequest $request)
    {
        try {
            // Images can be uploaded as 1. files or as 2. data urls
            $file = $request->file('image') ?? $request->file('file');
            $data = $file ?? $request->image;
            $originalName = $file !== null
                ? $file->getClientOriginalName()
                : $request->name;

            if (! $data) {
                return response()->json(['error' => __('files.errors.image_missing')], 400);
            }

            if (! $originalName) {
                return response()->json(['error' => __('files.errors.file_name_missing')], 400);
            }

            $path = (string) $request->input('path');

            // Every branch below is authorized. Previously only the FileManager branch was,
            // which left the shared image folders and any unrecognised path ungated.
            if (StoragePath::hasTraversal($path)) {
                return response()->json(['error' => __('files.errors.invalid_directory_path')], 422);
            }

            if ($this->isSharedImageFolder($path)) {
                // The shared folders (banners, news, ...) are not tenant-scoped, so they are
                // gated on the plain "may create files" ability rather than on a directory.
                if ($request->user()->cannot('create', File::class)) {
                    return response()->json(['error' => __('files.errors.no_upload_permission')], 403);
                }
            } elseif (! $this->isTipTapUpload($path)) {
                // Anything that is neither a shared folder nor a TipTap content path is treated
                // as a FileManager path and must clear the directory policy.
                $validatedPath = $this->validateAndNormalizePath($path);

                if (! $request->user()->can('viewDirectory', [File::class, $validatedPath])) {
                    return response()->json(['error' => __('files.errors.no_upload_permission')], 403);
                }
            }

            // Determine upload directory based on path structure
            $directory = $this->resolveUploadDirectory($path, $request->user());

            // Use ImageUploadService for processing and saving
            $result = $this->imageUploadService->processAndSave($data, $directory, $originalName);

            // Log upload
            Log::info('Image uploaded via FilesController', [
                'original_name' => $originalName,
                'processed_name' => $result['name'],
                'directory' => $directory,
                'original_size' => $result['originalSize'],
                'compressed_size' => $result['compressedSize'],
                'compression_ratio' => $result['compressionRatio'],
                'user_id' => $request->user()->id,
            ]);

            // Create success message
            $shortOriginalName = ImageUploadService::shortenFilename($originalName);
            $originalSizeKB = round($result['originalSize'] / 1024, 1);
            $compressedSizeKB = round($result['compressedSize'] / 1024, 1);

            $successMessage = "{$shortOriginalName} optimized and converted to WebP";
            $detailMessage = "Compressed from {$originalSizeKB} KB to {$compressedSizeKB} KB ({$result['compressionRatio']}% saved)";

            $uploadResult = [
                'url' => $result['url'],
                'name' => $result['name'],
                'originalSize' => $result['originalSize'],
                'compressedSize' => $result['compressedSize'],
                'compressionRatio' => $result['compressionRatio'],
                'message' => $successMessage,
            ];

            // Return Inertia response if request is from Inertia, otherwise JSON
            if ($request->header('X-Inertia')) {
                return back()->with('data', $uploadResult)->with('success', $successMessage)->with('toast_description', $detailMessage);
            }

            // Return JSON response for non-Inertia requests (backward compatibility)
            return response()->json($uploadResult);

        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'request_data' => $request->only(['name', 'path']),
            ]);

            $errorMessage = __('files.errors.image_processing_failed', ['error' => $e->getMessage()]);

            // Return Inertia response if request is from Inertia, otherwise JSON
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['upload' => $errorMessage]);
            }

            return response()->json([
                'error' => $errorMessage,
            ], 500);
        }
    }

    /**
     * Resolve the upload directory based on path and user context.
     */
    protected function resolveUploadDirectory(string $path, $user): string
    {
        // TipTap uploads: use tenant-based content directory logic
        if ($this->isTipTapUpload($path)) {
            return $this->resolveTipTapDirectory($user);
        }

        // One of the shared image folders the admin forms upload to.
        if ($this->isSharedImageFolder($path)) {
            return $path;
        }

        // FileManager uploads: use the normalized path, minus the disk's `public/` root.
        return str_replace('public/', '', $this->validateAndNormalizePath($path));
    }

    /**
     * Resolve TipTap content directory based on user's tenant.
     */
    protected function resolveTipTapDirectory($user): string
    {
        return app(FileStorageService::class)->resolveTipTapDirectory($user, $this->authorizer);
    }

    /**
     * Whether the path targets the TipTap content tree, whose real directory is derived from
     * the user's tenant rather than from the request.
     */
    protected function isTipTapUpload(string $path): bool
    {
        return FileStorageService::isTipTapPath($path);
    }

    /**
     * Whether the path names one of the shared, non-tenant-scoped image folders the admin
     * forms upload into (`<ImageUpload folder="...">`).
     *
     * This is an allowlist on purpose: the branch writes the caller's string straight through
     * as a directory name, so accepting arbitrary bare folder names would let a request pick
     * its own destination.
     */
    protected function isSharedImageFolder(string $path): bool
    {
        return in_array($path, self::SHARED_IMAGE_FOLDERS, true);
    }

    /**
     * Check if this is a FileManager upload (has full path structure).
     */
    protected function isFileManagerUpload(string $path): bool
    {
        return str_starts_with($path, 'public/files') || str_contains($path, '/files/');
    }

    public function compressImage(FilePathRequest $request)
    {
        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return back()->withErrors(['error' => __('files.errors.not_compressible')]);
        }

        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            return back()->withErrors(['permission' => __('files.errors.no_modify_permission')]);
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

    public function delete(FilePathRequest $request)
    {
        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        // Check if user has permission to delete files in this directory
        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            return back()->withErrors(['permission' => __('files.errors.no_delete_permission')]);
        }

        // Additional safety check: ensure file exists and is within allowed directory
        if (! Storage::exists($path)) {
            return back()->withErrors(['file' => __('files.errors.file_not_found')]);
        }

        // Verify the file is actually a file, not a directory
        if (Storage::directoryExists($path)) {
            return back()->withErrors(['file' => __('files.errors.cannot_delete_directory_this_way')]);
        }

        // Get file name for success message
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

    public function bulkDelete(BulkDeleteFilesRequest $request)
    {
        $paths = $request->input('paths');
        $deletedCount = 0;
        $errors = [];
        $skippedCount = 0;

        foreach ($paths as $path) {
            try {
                $validatedPath = $this->validateAndNormalizePath($path);

                // Check permissions for each file
                $directoryPath = dirname($validatedPath);
                if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
                    $errors[] = __('files.errors.bulk_no_delete_permission', ['name' => basename($path)]);
                    $skippedCount++;

                    continue;
                }

                // Safety checks
                if (! Storage::exists($validatedPath)) {
                    $errors[] = __('files.errors.bulk_file_not_found', ['name' => basename($path)]);
                    $skippedCount++;

                    continue;
                }

                if (Storage::directoryExists($validatedPath)) {
                    $errors[] = __('files.errors.bulk_is_directory', ['name' => basename($path)]);
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
                    'file_name' => basename($path),
                ]);

            } catch (\InvalidArgumentException) {
                $errors[] = __('files.errors.bulk_invalid_path', ['name' => basename($path)]);
                $skippedCount++;
            } catch (\Exception $e) {
                $errors[] = __('files.errors.bulk_delete_error', ['name' => basename($path)]);
                $skippedCount++;
                Log::error('Bulk delete error', [
                    'path' => $path,
                    'user_id' => $request->user()->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Prepare response message
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

    public function deleteDirectory(FilePathRequest $request)
    {
        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_folder_path')]);
        }

        // Ensure we're not trying to delete the root directory
        if ($path === 'public/files') {
            return back()->withErrors(['error' => __('files.errors.cannot_delete_root')]);
        }

        // Check if user has permission to delete directories in the parent directory
        $parentDirectory = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $parentDirectory])) {
            return back()->withErrors(['permission' => __('files.errors.no_directory_delete_permission')]);
        }

        // Additional safety check: ensure directory exists
        if (! Storage::directoryExists($path)) {
            return back()->withErrors(['directory' => __('files.errors.directory_not_found')]);
        }

        // Check if directory is empty
        $files = Storage::files($path);
        $subdirectories = Storage::directories($path);

        if (count($files) > 0 || count($subdirectories) > 0) {
            return back()->withErrors(['directory' => __('files.errors.directory_not_empty')]);
        }

        // Get directory name for success message
        $directoryName = basename($path);

        try {
            // Remove 'public/' from the start for Storage::disk('public')
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

    /**
     * Get allowed file types for frontend validation
     */
    public function getAllowedFileTypes()
    {
        return response()->json([
            'extensions' => StoreFilesRequest::getAllowedExtensions(),
            'accept' => '.'.implode(',.', StoreFilesRequest::getAllowedExtensions()),
            'maxSizeMB' => 50,
        ]);
    }

    /**
     * Scan file usage across all TipTap-enabled models
     */
    public function scanFileUsage(FilePathRequest $request, FileUsageScanner $scanner)
    {
        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        // Check if user has permission to view this file
        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            return back()->withErrors(['error' => __('files.errors.no_scan_permission')]);
        }

        // Additional safety check: ensure file exists
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

            // Create appropriate success message
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
