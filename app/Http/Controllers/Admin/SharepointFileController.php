<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SharepointFile;
use App\Models\Type;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ResourceServices\SharepointFileableService;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\SharepointGraphService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharepointFileController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SharepointFile::class);

        $graph = new SharepointGraphService;

        $path = $request->get('path');

        $path = $path ?? 'General';

        return Inertia::render('Admin/Files/IndexSharepoint', [
            'path' => $path,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', SharepointFile::class);

        $validated = $request->validate([
            'file' => 'required',
            'fileable' => 'required',
        ]);

        $fileable_class = 'App\\Models\\'.$validated['fileable']['type'];

        // check if fileable class exists
        if (! class_exists($fileable_class)) {
            return back()->with('error', 'Failas negali būti priskirtas objektui.');
        }

        // check if fileable exists
        $fileable = $fileable_class::find($validated['fileable']['id']);
        if (! $fileable) {
            return back()->with('error', 'Susijęs objektas neegzistuoja.');
        }

        // check if fileable is allowed to have files
        if (! method_exists($fileable, 'files')) {
            return back()->with('error', 'Susijęs objektas negali turėti failų.');
        }

        $sharepointFileService = new SharepointFileService;
        $sharepointFileableService = new SharepointFileableService;

        $listItemProperties = [
            'Type' => $validated['file']['typeValue'],
            'Description0' => $validated['file']['description0Value'],
            'Keywords' => $validated['file']['keywordsValue'] ?? [],
            'Keywords@odata.type' => 'Collection(Edm.String)',
            'Date' => date('Y-m-d', intval($validated['file']['datetimeValue'] / 1000)),
        ];

        $sharepointFile = $sharepointFileService->uploadFile($request->file('file')['uploadValue']['file'], $validated['file']['nameValue'], $fileable, $listItemProperties);
        // sharepoint fileable concern - attach sharepoint file to fileable
        $sharepointFileableService->attachFileToFileable($sharepointFile, $fileable);

        return back()->with('success', 'Failas sėkmingai įkeltas į Sharepoint!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, SharepointFile $sharepointFile)
    {
        $this->authorize('delete', $sharepointFile);

        $sharepointFileService = new SharepointGraphService;

        $sharepointFileService->deleteDriveItem($sharepointFile->sharepoint_id);

        return back()->with('info', 'Failas ištrintas.');
    }

    // get potential fileables, usually when none specified
    public function getPotentialFileables(Request $request)
    {
        return response()->json([
            'institutions' => Institution::with('meetings:meetings.id,start_time')->whereHas('tenant')->get()->map->only('id', 'name', 'meetings'),
            'types' => Type::all()->map->only('id', 'title'),
        ]);
    }

    public function getDriveItems(Request $request)
    {
        // $this->authorize('viewAll', [SharepointFile::class, $this->authorizer]);

        $sharepointService = new SharepointGraphService;

        $path = $request->get('path');

        // remove trailing slash
        $path = rtrim($path, '/');

        // TODO: need to authorize by path
        $driveItems = $sharepointService->getDriveItemByPath($path, true);

        return response()->json($driveItems);
    }

    public function getTypesDriveItems(Request $request, string $type, string $id)
    {
        // $validated = $request->validate([
        //     'fileable' => 'required',
        // ]);

        $fileable_class = 'App\\Models\\'.$type;

        // check if fileable class exists
        if (! class_exists($fileable_class)) {
            return back()->with('info', 'Neteisinga užklausa. Praneškite administratoriui');
        }

        // check if fileable exists
        $fileable = $fileable_class::find($id);

        if (! $fileable) {
            return back()->with('info', 'Neteisinga užklausa. Praneškite administratoriui');
        }

        $types = $fileable->types->map(function ($type) {
            return $type->getParentsAndSelf();
        })->flatten()->unique('id')->values();

        // types array to string
        // TODO: maybe use 'pluck' instead of 'map'?
        $types_string = $types->pluck('id')->implode(',');

        $sharepointService = new SharepointGraphService;

        // get all types paths into one array
        $paths = $types->map(function ($type) {
            return $type->sharepoint_path();
        })->toArray();

        if (empty($paths)) {
            return;
        }

        $driveItems = $sharepointService->getDriveItemsChildrenByPaths($paths);

        return response()->json($driveItems);
    }

    public function getDriveItemPublicLink(Request $request, string $driveItemId)
    {
        $sharepointService = new SharepointGraphService;

        $permission = $sharepointService->getDriveItemPublicLink($driveItemId);

        if (! $permission) {
            return response()->json(null);
        }

        $link = $permission->getLink()->getWebUrl();

        return response()->json($link);
    }

    public function createPublicPermission(Request $request, string $driveItemId)
    {
        $sharepointService = new SharepointGraphService;

        $permission = $sharepointService->createPublicPermission(
            siteId: $sharepointService->siteId, driveItemId: $driveItemId);

        $link = $permission->getLink()->getWebUrl();

        return response()->json($link);
    }
}
