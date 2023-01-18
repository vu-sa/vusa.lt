<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SharepointFile;
use App\Services\SharepointService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharepointController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sharepointService = new SharepointService();

        $driveItems = $sharepointService->getDriveItemChildrenItems();

        return Inertia::render('Admin/Files/IndexSharepoint', [
            'sharepointDriveItems' => $sharepointService->parseDriveItems($driveItems),
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
        //
    }
}
