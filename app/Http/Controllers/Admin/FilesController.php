<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Intervention\Image\Laravel\Facades\Image;

class FilesController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    protected function getFilesFromStorage($path)
    {
        $directories = Storage::directories($path);
        $files = Storage::files($path);

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
        $path = $request->path ?? 'public/files';

        //# If path doesn't have public/files in it, change it to public/files

        if (! str_contains($path, 'public/files')) {
            $path = 'public/files';
        }

        // Check if can view directory
        if (! $request->user()->can('viewAny', [File::class, $path])) {

            // If not, redirect to padaliniai/{padalinys}
            if ($this->authorizer->getTenants()->count() > 0) {
                $path = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;
            } else {
                // Redirect to dashboard home
                return redirect()->route('dashboard');
            }
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
        $path = $request->path ?? 'public/files';

        if (! str_contains($path, 'public/files')) {
            $path = 'public/files';
        }

        // Check if can view directory
        if (! $request->user()->can('viewAny', [File::class, $path])) {

            // If not, redirect to padaliniai/{padalinys}
            if ($this->authorizer->getTenants()->count() > 0) {
                $path = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;
            } else {
                // Return error response
                return response()->json([
                    'error' => 'You do not have permission to view this directory.',
                ], 403);
            }
        }

        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

        return response()->json([
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $file = $request->file('file')['file'];

        // limit file size to 50 MB
        if ($file->getSize() > 50000000) {
            return response()->json([
                'error' => 'File is too large.',
            ], 422);
        }

        $path = $request->input('path');

        // Check if file exists, if so, add timestamp to filename
        if (Storage::exists($path.'/'.$file->getClientOriginalName())) {
            $file->storeAs($path, time().'_'.$file->getClientOriginalName());
        } else {
            $file->storeAs($path, $file->getClientOriginalName());
        }

        // return redirect to files index
        return back();
    }

    public function createDirectory(Request $request)
    {
        $path = $request->input('path');
        $name = $request->input('name');

        // Remove 'public' only from the start of the path, because it's already in the 'public' fs path
        $path = str_replace('public/', '', $path);

        // check if directory exists
        if (! Storage::exists($path.'/'.$name)) {
            Storage::disk('public')->makeDirectory($path.'/'.$name);
        }

        // return redirect to files index
        return back();
    }

    public function uploadImage(Request $request)
    {
        // Images can be uploaded as 1. files or as 2. data urls

        $data = $request->file()['file'] ?? $request->image;
        $originalName = isset($request->file()['file']) ? $request->file()['file']->getClientOriginalName() : $request->name;

        $image = Image::read($data);

        $image->scaleDown(width: 1200);

        $path = (string) $request->input('path');

        // check if path exists
        if (! Storage::exists('public/'.$path)) {
            Storage::makeDirectory('public/'.$path);
        }

        // save image to storage
        // check if image exists
        if (Storage::exists('public/'.$path.'/'.$originalName)) {
            $originalName = time().'_'.$originalName;
        }

        $image->save(storage_path('app/public/'.$path.'/'.$originalName), 80);

        // return xhr response with image path
        return response()->json([
            'url' => '/uploads/'.$path.'/'.$originalName,
        ]);
    }

    public function delete(Request $request)
    {
        $path = $request->input('path');

        if (! str_contains($path, 'public/files')) {
            // Return with error
            return response()->json([
                'error' => 'You do not have permission to delete this file.',
            ], 403);
        }

        if (! $request->user()->can('delete', $path)) {

            // If not, redirect to padaliniai/{padalinys}
            if ($this->authorizer->getTenants()->count() > 0) {
                $path = 'public/files/padaliniai/vusa'.$this->authorizer->getTenants()->first()->alias;
            } else {
                // Redirect to dashboard home
                return redirect()->route('dashboard');
            }
        }

        // check if file exists
        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        // return redirect to files index
        return back();
    }
}
