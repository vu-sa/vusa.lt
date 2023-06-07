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
        $currentDirectory = $request->currentPath ?? 'public/files';

        $directories = Storage::directories($currentDirectory);
        $files = Storage::files($currentDirectory);

        // dd($files, $directories);

        return Inertia::render('Admin/Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'currentPath' => $currentDirectory,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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

        // add file to storage
        $file->storeAs($path, $file->getClientOriginalName());

        // return redirect to files index
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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
