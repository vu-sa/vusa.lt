<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FilesController extends Controller
{
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
     * @param  \Illuminate\Http\Request  $request
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
        
        $currentDirectory = $request->currentPath ?? 'public/files';

        $allfiles = Storage::allfiles($currentDirectory);

        // filter files by search term
        $files = collect($allfiles)->filter(function ($file) use ($data) {
            // search str_contains with case insensitive
            return str_contains(strtolower($file), strtolower($data['search']));
        });

        return back()->with('search_other', $files);
    }
}
