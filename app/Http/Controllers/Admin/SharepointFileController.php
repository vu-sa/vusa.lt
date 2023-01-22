<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\Institution;
use App\Models\SharepointFile;
use App\Models\Type;
use App\Services\ResourceServices\SharepointFileableService;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\SharepointGraphService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharepointFileController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [SharepointFile::class, $this->authorizer]);
        
        $path = request()->get('path');

        $path = $path ?? 'General';

        return Inertia::render('Admin/Files/IndexSharepoint', [
            'path' => $path,
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
        $this->authorize('create', [SharepointFile::class, $this->authorizer]);
        
        $validated = $request->validate([
            'file' => 'required',
            'fileable' => 'required',
        ]);

        $fileable_class = 'App\\Models\\' . $validated['fileable']['type'];

        // check if fileable class exists
        if (!class_exists($fileable_class)) {
            return back()->with('error', 'Failas negali būti priskirtas objektui.');
        }

        // check if fileable exists
        $fileable = $fileable_class::find($validated['fileable']['id']);
        if (!$fileable) {
            return back()->with('error', 'Susijęs objektas neegzistuoja.');
        }

        // check if fileable is allowed to have files
        if (!method_exists($fileable, 'files')) {
            return back()->with('error', 'Susijęs objektas negali turėti failų.');
        }

        $fileToUpload = $request->file('file')['uploadValue']['file'];

        $sharepointFileService = new SharepointFileService();
        $sharepointFileableService  = new SharepointFileableService();

        $listItemProperties = [
            'Type' => $validated['file']['typeValue'],
            'Description0' => $validated['file']['description0Value'],
            'Keywords' => $validated['file']['keywordsValue'] ?? [],
            'Keywords@odata.type' => "Collection(Edm.String)",
            'Date' => date('Y-m-d', intval($validated['file']['datetimeValue'] / 1000)),
        ];

        $sharepointFile = $sharepointFileService->uploadFile($fileToUpload, $validated['file']['nameValue'], $fileable, $listItemProperties);
        // sharepoint fileable concern - attach sharepoint file to fileable
        $sharepointFileableService->attachFileToFileable($sharepointFile, $fileable);

        return back()->with('success', 'Failas sėkmingai įkeltas į Sharepoint!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SharepointFile  $sharepointFile
     * @return \Illuminate\Http\Response
     */
    public function show(SharepointFile $sharepointFile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SharepointFile  $sharepointFile
     * @return \Illuminate\Http\Response
     */
    public function edit(SharepointFile $sharepointFile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SharepointFile  $sharepointFile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SharepointFile $sharepointFile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SharepointFile  $sharepointFile
     * @return \Illuminate\Http\Response
     */
    public function destroy(SharepointFile $sharepointFile)
    {
        $this->authorize('delete', [SharepointFile::class, $sharepointFile, $this->authorizer]);

        $sharepointFileService = new SharepointGraphService();

        $sharepointFileService->deleteDriveItem($sharepointFile);

        return back()->with('success', 'Failas sėkmingai ištrintas iš Sharepoint.');
    }

    // get potential fileables, usually when none specified
    public function getPotentialFileables(Request $request)
    {
        return response()->json([
            'institutions' => Institution::with('meetings:meetings.id,start_time')->whereHas('padalinys')->get()->map->only('id', 'name', 'meetings'),
            'types' => Type::all()->map->only('id', 'title'),
        ]);
    }

    public function getDriveItems(Request $request)
    {
        $this->authorize('viewAll', [SharepointFile::class, $this->authorizer]);

        $sharepointService = new SharepointGraphService();

        $path = $request->get('path');

        // remove trailing slash
        $path = rtrim($path, '/');

        $driveItems = $sharepointService->getDriveItemByPath($path, true);

        return response()->json($driveItems);
    }
}
