<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\FileUsageScanner;
use App\Services\ImageUploadService;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FilesController extends AdminController
{
    public function __construct(
        public Authorizer $authorizer,
        protected ImageUploadService $imageUploadService
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
            // If it contains a slash treat as relative subpath, else treat as file in root files dir
            $path = str_contains($path, '/')
                ? 'public/files/'.$path
                : 'public/files/'.$path; // single filename
        }

        // Normalize path separators and remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);
        $path = rtrim($path, '/');

        // Additional security: allow Unicode letters, marks (for combining diacritics), numbers, underscores,
        // hyphens, dots, spaces, parentheses, and forward slashes
        if (! preg_match('/^[\p{L}\p{M}\p{N}\/_. ()-]+$/u', $path)) {
            throw new \InvalidArgumentException('Invalid path format');
        }

        return $path;
    }

    protected function getFilesFromStorage($path)
    {
        $path = $this->validateAndNormalizePath($path);

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
        } catch (\InvalidArgumentException $e) {
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
                        ->with('info', 'Nukreiptas į jūsų padalinio failų aplanką.');
                }
            }

            // If no access to tenant directory, redirect to dashboard
            return $this->redirectResponse('dashboard')->with('error', 'Neturite teisių peržiūrėti failų systemos. Kreipkitės į administratorių dėl prieigos teisių.');
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
                        session()->flash('success', 'Nukreiptas į jūsų padalinio failų aplanką.');
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
                            'error' => 'Nepavyko gauti failų sąrašo po nukreipimo.',
                            'code' => 'FETCH_ERROR',
                        ], 500);
                    }
                }
            }

            return response()->json([
                'error' => 'Neturite teisių peržiūrėti šio aplanko.',
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
                'error' => 'Nepavyko gauti failų sąrašo. Bandykite dar kartą.',
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
            // TipTap uploads: use tenant-based content directory logic
            if ($request->user()->hasRole(config('permission.super_admin_role_name'))) {
                // Super admins always upload to global content directory
                $path = 'files/content/'.date('Y/m');
            } elseif ($this->authorizer->getTenants()->count() > 0) {
                $tenant = $this->authorizer->getTenants()->first();

                // Check if this is the main tenant (type 'pagrindinis')
                if ($tenant->type === 'pagrindinis') {
                    // Main tenant uploads to root content directory
                    $path = 'files/content/'.date('Y/m');
                } else {
                    // Other tenants upload to their specific directory
                    $path = "files/padaliniai/vusa{$tenant->alias}/content/".date('Y/m');
                }
            } else {
                // Fallback for users with no tenant (shouldn't happen)
                $path = 'files/content/'.date('Y/m');
            }
        } else {
            // FileManager uploads: validate path normally
            try {
                $path = $this->validateAndNormalizePath($path);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['path' => 'Neteisingas katalogo kelias.']);
            }

            // Check if user has permission to upload to this directory
            if (! $request->user()->can('viewDirectory', [File::class, $path])) {
                return back()->withErrors(['permission' => 'Neturite teisių įkelti failų į šį aplanką.']);
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
            $messages[] = "Įkelta {$uploadedCount} ".
                ($uploadedCount === 1 ? 'failas' : ($uploadedCount < 10 ? 'failai' : 'failų'));
        }
        if ($renamedCount > 0) {
            $messages[] = "{$renamedCount} ".
                ($renamedCount === 1 ? 'failas pervardytas' : ($renamedCount < 10 ? 'failai pervardyti' : 'failų pervardita')).
                ' (egzistavo tokie pat pavadinimai)';
        }

        if (! empty($messages)) {
            $successMessage = implode(', ', $messages).'.';
            if (! empty($errors)) {
                $successMessage .= ' Nepavyko įkelti: '.implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $successMessage .= ' ir dar '.(count($errors) - 3).'...';
                }

                return back()->with('warning', $successMessage);
            }

            return back()->with('success', $successMessage);
        } else {
            return back()->withErrors(['error' => 'Nepavyko įkelti nei vieno failo.']);
        }
    }

    public function createDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|max:255|regex:/^[\p{L}\p{N}_\- ]+$/u',
        ], [
            'name.regex' => 'Aplanko pavadinimas gali turėti tik raides, skaičius, pabraukimus, brūkšnelius ir tarpus.',
            'name.required' => 'Aplanko pavadinimas yra privalomas.',
            'name.max' => 'Aplanko pavadinimas negali būti ilgesnis nei :max simbolių.',
        ]);

        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['path' => 'Neteisingas katalogo kelias.']);
        }

        $name = trim($request->input('name'));

        // Check if user has permission to create directories in this path
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
            return back()->withErrors(['permission' => 'Neturite teisių kurti aplankų šioje vietoje.']);
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

            return back()->with('success', 'Aplankas "'.$name.'" sėkmingai sukurtas.');
        } catch (\Exception $e) {
            Log::error('Error creating directory', [
                'path' => $newDirectoryPath,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'name' => $name,
            ]);

            return back()->withErrors(['error' => 'Nepavyko sukurti aplanko. Bandykite dar kartą.']);
        }
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|max:51200', // 50MB max
            'name' => 'nullable|string|max:255',
            'path' => 'required|string',
        ], [
            'path.required' => 'Kelias yra privalomas.',
            'name.max' => 'Failo pavadinimas per ilgas.',
            'image.image' => 'Failas turi būti paveikslėlis.',
            'image.max' => 'Paveikslėlis negali būti didesnis nei 50MB.',
        ]);

        try {
            // Images can be uploaded as 1. files or as 2. data urls
            $file = $request->file('image') ?? $request->file('file');
            $data = $file ?? $request->image;
            $originalName = $file !== null
                ? $file->getClientOriginalName()
                : $request->name;

            if (! $data) {
                return response()->json(['error' => 'Nepateiktas paveikslėlis.'], 400);
            }

            if (! $originalName) {
                return response()->json(['error' => 'Nepateiktas failo pavadinimas.'], 400);
            }

            $path = (string) $request->input('path');

            // Determine upload directory based on path structure
            $directory = $this->resolveUploadDirectory($path, $request->user());

            // Check permissions for FileManager uploads
            if ($this->isFileManagerUpload($path)) {
                $validatedPath = $this->validateAndNormalizePath($path);
                if (! $request->user()->can('viewDirectory', [File::class, $validatedPath])) {
                    return response()->json(['error' => 'Neturite teisių įkelti failų į šį aplanką.'], 403);
                }
            }

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

            $errorMessage = 'Nepavyko apdoroti paveikslėlio: '.$e->getMessage();

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
        if (str_starts_with($path, 'content/')) {
            return $this->resolveTipTapDirectory($user);
        }

        // Simple folder name (e.g., 'banners', 'news', 'calendar')
        if (! str_contains($path, '/') && ! str_starts_with($path, 'public/')) {
            return $path;
        }

        // FileManager uploads: use the provided path directly
        return str_replace('public/', '', $path);
    }

    /**
     * Resolve TipTap content directory based on user's tenant.
     */
    protected function resolveTipTapDirectory($user): string
    {
        if ($user->hasRole(config('permission.super_admin_role_name'))) {
            return 'files/content/'.date('Y/m');
        }

        if ($this->authorizer->getTenants()->count() > 0) {
            $tenant = $this->authorizer->getTenants()->first();

            // Main tenant uploads to root content directory
            if ($tenant->type === 'pagrindinis') {
                return 'files/content/'.date('Y/m');
            }

            // Other tenants upload to their specific directory
            return "files/padaliniai/vusa{$tenant->alias}/content/".date('Y/m');
        }

        return 'files/content/'.date('Y/m');
    }

    /**
     * Check if this is a FileManager upload (has full path structure).
     */
    protected function isFileManagerUpload(string $path): bool
    {
        return str_starts_with($path, 'public/files') || str_contains($path, '/files/');
    }

    public function compressImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => 'Neteisingas failo kelias.']);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return back()->withErrors(['error' => 'Failo formatas negali būti suspaustas.']);
        }

        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            return back()->withErrors(['permission' => 'Neturite teisių modifikuoti failų šiame aplanke.']);
        }

        if (! \Storage::exists($path) || \Storage::directoryExists($path)) {
            return back()->withErrors(['error' => 'Failas nerastas.']);
        }

        try {
            $fullLocalPath = storage_path('app/'.$path);
            $originalSize = filesize($fullLocalPath) ?: 0;

            $image = \Intervention\Image\Laravel\Facades\Image::read($fullLocalPath);
            $image->scaleDown(width: 1600);
            $quality = $originalSize > 2 * 1024 * 1024 ? 72 : 78; // 2MB threshold

            // Always keep original extension (no conversion to webp)
            if (in_array($extension, ['jpg', 'jpeg'])) {
                $image->toJpeg($quality);
            } elseif ($extension === 'png') {
                // For PNG we can optionally reduce palette; Intervention's toPng keeps format
                $image->toPng();
            }

            $image->save($fullLocalPath);
            clearstatcache();
            $newSize = filesize($fullLocalPath) ?: $originalSize;
            $saved = $originalSize > 0 ? round((1 - $newSize / $originalSize) * 100) : 0;

            \Log::info('Image compressed', [
                'path' => $path,
                'converted_to_webp' => false,
                'original_size' => $originalSize,
                'new_size' => $newSize,
                'percent_saved' => $saved,
                'user_id' => $request->user()->id,
            ]);

            $fileName = basename($path);
            $msg = 'Paveikslėlis optimizuotas (sutaupyta '.$saved.'%).';

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

            return back()->withErrors(['error' => 'Nepavyko optimizuoti paveikslėlio: '.$e->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => 'Neteisingas failo kelias.']);
        }

        // Check if user has permission to delete files in this directory
        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            return back()->withErrors(['permission' => 'Neturite teisių trinti failų šiame aplanke.']);
        }

        // Additional safety check: ensure file exists and is within allowed directory
        if (! Storage::exists($path)) {
            return back()->withErrors(['file' => 'Failas nerastas.']);
        }

        // Verify the file is actually a file, not a directory
        if (Storage::directoryExists($path)) {
            return back()->withErrors(['file' => 'Negalima trinti aplankų šiuo būdu.']);
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

            return back()->with('success', 'Failas "'.$fileName.'" sėkmingai ištrintas.');
        } catch (\Exception $e) {
            Log::error('Error deleting file', [
                'path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'file_name' => $fileName,
            ]);

            return back()->withErrors(['error' => 'Nepavyko ištrinti failo. Bandykite dar kartą.']);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'paths' => 'required|array|min:1|max:50', // Limit to 50 files for safety
            'paths.*' => 'required|string',
        ], [
            'paths.required' => 'Nepasirinktas nei vienas failas trinimui.',
            'paths.max' => 'Per daug failų pasirinkta. Maksimalus kiekis: :max.',
            'paths.min' => 'Nepasirinktas nei vienas failas trinimui.',
        ]);

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
                    $errors[] = 'Nėra teisių trinti: '.basename($path);
                    $skippedCount++;

                    continue;
                }

                // Safety checks
                if (! Storage::exists($validatedPath)) {
                    $errors[] = 'Failas nerastas: '.basename($path);
                    $skippedCount++;

                    continue;
                }

                if (Storage::directoryExists($validatedPath)) {
                    $errors[] = 'Negalima trinti aplanko: '.basename($path);
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

            } catch (\InvalidArgumentException $e) {
                $errors[] = 'Neteisingas kelias: '.basename($path);
                $skippedCount++;
            } catch (\Exception $e) {
                $errors[] = 'Klaida trinant: '.basename($path);
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
            return back()->with('success', "Sėkmingai ištrinta {$deletedCount} ".
                ($deletedCount === 1 ? 'failas' : ($deletedCount < 10 ? 'failai' : 'failų')).'.');
        } elseif ($deletedCount > 0 && $skippedCount > 0) {
            $message = "Ištrinta {$deletedCount} ".
                ($deletedCount === 1 ? 'failas' : ($deletedCount < 10 ? 'failai' : 'failų'));
            if (! empty($errors)) {
                $message .= ". Praleista {$skippedCount}: ".implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= ' ir dar '.(count($errors) - 3).'...';
                }
            }

            return back()->with('warning', $message);
        } else {
            $errorMessage = 'Nepavyko ištrinti nei vieno failo.';
            if (! empty($errors)) {
                $errorMessage .= ' Klaidos: '.implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $errorMessage .= ' ir dar '.(count($errors) - 3).'...';
                }
            }

            return back()->withErrors(['error' => $errorMessage]);
        }
    }

    public function deleteDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => 'Neteisingas aplanko kelias.']);
        }

        // Ensure we're not trying to delete the root directory
        if ($path === 'public/files') {
            return back()->withErrors(['error' => 'Negalima ištrinti šakninio failų aplanko.']);
        }

        // Check if user has permission to delete directories in the parent directory
        $parentDirectory = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $parentDirectory])) {
            return back()->withErrors(['permission' => 'Neturite teisių trinti aplankų šioje vietoje.']);
        }

        // Additional safety check: ensure directory exists
        if (! Storage::directoryExists($path)) {
            return back()->withErrors(['directory' => 'Aplankas nerastas.']);
        }

        // Check if directory is empty
        $files = Storage::files($path);
        $subdirectories = Storage::directories($path);

        if (count($files) > 0 || count($subdirectories) > 0) {
            return back()->withErrors(['directory' => 'Aplankas nėra tuščias. Pirmiausia ištrinkite visus failus ir poaplankus.']);
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

            return back()->withErrors(['error' => 'Nepavyko ištrinti aplanko. Bandykite dar kartą.']);
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
    public function scanFileUsage(Request $request, FileUsageScanner $scanner)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $this->validateAndNormalizePath($request->input('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => 'Neteisingas failo kelias.']);
        }

        // Check if user has permission to view this file
        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            return back()->withErrors(['error' => 'Neturite teisių skenuoti šio failo naudojimą.']);
        }

        // Additional safety check: ensure file exists
        if (! Storage::exists($path)) {
            return back()->withErrors(['error' => 'Failas nerastas.']);
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
                $message = 'Failas saugus trinti - nerasta jokių naudojimų '.count($usageData['scanned_models']).' turinio tipuose.';

                return back()->with('data', $usageData)->with('success', $message);
            } else {
                $message = "Rasta {$usageData['total_usages']} naudojimų - peržiūrėkite detales prieš trinant.";

                return back()->with('data', $usageData)->with('info', $message);
            }
        } catch (\Exception $e) {
            Log::error('File usage scan failed', [
                'file_path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Nepavyko nuskaityti failo naudojimo: '.$e->getMessage()]);
        }
    }
}
