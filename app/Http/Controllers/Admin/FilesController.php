<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFilesRequest;
use App\Models\File;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Intervention\Image\Laravel\Facades\Image;

class FilesController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Safely validate and normalize file path
     */
    protected function validateAndNormalizePath(string $path): string
    {
        // Remove any path traversal attempts
        $path = str_replace(['../', '..\\', '../', '..\\'], '', $path);

        // Ensure path starts with public/files
        if (! str_starts_with($path, 'public/files')) {
            $path = 'public/files';
        }

        // Normalize path separators and remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);
        $path = rtrim($path, '/');

        // Additional security: allow common filename characters including Lithuanian characters
        // Allow letters (including Unicode), numbers, underscores, hyphens, dots, spaces, and forward slashes
        if (! preg_match('/^[\p{L}\p{N}\/_. -]+$/u', $path)) {
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

        $files = collect(Storage::files($path))->map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'type' => 'file',
                'size' => Storage::size($file),
                'modified' => Storage::lastModified($file),
                'mimeType' => Storage::mimeType($file),
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
     *
     * @return \Illuminate\Http\Response
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
                $allowedPath = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;

                // Check if user can access their tenant directory
                if ($request->user()->can('viewDirectory', [File::class, $allowedPath])) {
                    return redirect()->route('files.index', ['path' => $allowedPath])
                        ->with('info', 'Nukreiptas į jūsų padalinio failų aplanką.');
                }
            }

            // If no access to tenant directory, redirect to dashboard
            return redirect()->route('dashboard')->with('error', 'Neturite teisių peržiūrėti failų systemos. Kreipkitės į administratorių dėl prieigos teisių.');
        }

        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

        return Inertia::render('Admin/Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

    public function getFiles(Request $request)
    {
        try {
            $path = $this->validateAndNormalizePath($request->path ?? 'public/files');
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Invalid path format'], 400);
        }

        // Check if user can view this specific directory
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
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
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFilesRequest $request)
    {
        $validated = $request->validated();

        $files = $validated['files'];

        try {
            $path = $this->validateAndNormalizePath($validated['path']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['path' => 'Neteisingas katalogo kelias.']);
        }

        // Check if user has permission to upload to this directory
        if (! $request->user()->can('viewDirectory', [File::class, $path])) {
            return back()->withErrors(['permission' => 'Neturite teisių įkelti failų į šį aplanką.']);
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
                ($renamedCount === 1 ? 'failas pervardytas' : ($renamedCount < 10 ? 'failai pervardyti' : 'failų pervardyta')).
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
            'image' => 'nullable',
            'name' => 'nullable|string|max:255',
            'path' => 'required|string',
        ], [
            'path.required' => 'Kelias yra privalomas.',
            'name.max' => 'Failo pavadinimas per ilgas.',
        ]);

        // Images can be uploaded as 1. files or as 2. data urls
        $data = $request->file()['file'] ?? $request->image;
        $originalName = isset($request->file()['file'])
            ? $request->file()['file']->getClientOriginalName()
            : $request->name;

        if (! $data) {
            return response()->json(['error' => 'Nepateiktas paveikslėlis.'], 400);
        }

        if (! $originalName) {
            return response()->json(['error' => 'Nepateiktas failo pavadinimas.'], 400);
        }

        $startingImage = Image::read($data);
        $image = $startingImage->scaleDown(width: 1600)->toWebp();

        $path = (string) $request->input('path');

        // Get file name without extension and add .webp
        $originalName = pathinfo($originalName, PATHINFO_FILENAME).'.webp';

        // Check if path exists, create if it doesn't
        if (! Storage::exists('public/'.$path)) {
            Storage::makeDirectory('public/'.$path);
        }

        // Check if image exists and rename if needed
        if (Storage::exists('public/'.$path.'/'.$originalName)) {
            $originalName = time().'_'.$originalName;
        }

        $fullPath = storage_path('app/public/'.$path.'/'.$originalName);

        // Intervention Image save() returns null on success, not false
        $image->save($fullPath, 85);

        Log::info('Image uploaded and processed', [
            'original_name' => $request->name,
            'processed_name' => $originalName,
            'path' => 'public/'.$path,
            'user_id' => $request->user()->id,
        ]);

        // Return response with correct URL format
        return response()->json([
            'url' => '/uploads/'.$path.'/'.$originalName,
            'name' => $originalName,
            'message' => 'Paveikslėlis sėkmingai įkeltas ir optimizuotas.',
        ]);
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
}
