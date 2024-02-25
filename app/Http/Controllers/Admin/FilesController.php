<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Intervention\Image\Facades\Image;

class FilesController extends Controller
{
    // TODO: add authorization and just do something with whole this section
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $path = $request->path ?? 'public/files';

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

        [$files, $directories, $currentDirectory] = $this->getFilesFromStorage($path);

        return response()->json([
            'files' => $files,
            'directories' => $directories,
            'path' => $currentDirectory,
        ]);
    }

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

        // dd($file['file'], $path);
        //
        // check if file exists, if so, add timestamp to filename

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

        // check if directory exists
        if (! Storage::exists($path.'/'.$name)) {
            Storage::makeDirectory($path.'/'.$name);
        }

        // return redirect to files index
        return back();
    }

    public function searchForFiles(Request $request)
    {
        $data = $request->collect()['data'];

        $currentDirectory = $request->currentPath ?? 'public';

        $allfiles = Storage::allfiles($currentDirectory);

        // filter files by search term
        $files = collect($allfiles)->filter(function ($file) use ($data) {
            // search str_contains with case insensitive
            return str_contains(strtolower($file), strtolower($data['search']));
        });

        // dd($files);

        return back()->with('search_other', $files);
    }

    public function searchForImages(Request $request)
    {
        $data = $request->collect()['data'];

        $currentDirectory = $request->currentPath ?? 'public';

        $allfiles = Storage::allfiles($currentDirectory);

        // filter images by search term
        $images = collect($allfiles)->filter(function ($file) use ($data) {
            // search str_contains with case insensitive img or png
            return str_contains(strtolower($file), strtolower($data['search'])) && (str_contains(strtolower($file), '.png') || str_contains(strtolower($file), '.jpg' || str_contains(strtolower($file), '.jpeg')));
        });

        // dd($images);

        return back()->with('search_other', $images);
    }

    public function uploadImage(Request $request)
    {
        // Images can be uploaded as 1. files or as 2. data urls

        $data = $request->file()['file'] ?? $request->image;
        $originalName = isset($request->file()['file']) ? $request->file()['file']->getClientOriginalName() : $request->name;

        $image = Image::make($data)->orientate();

        $image->resize(1200, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

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
}
