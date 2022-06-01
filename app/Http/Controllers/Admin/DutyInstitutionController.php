<?php

namespace App\Http\Controllers\Admin;

use App\Models\DutyInstitution;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;
use App\Models\Padalinys;

class DutyInstitutionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DutyInstitution::class, 'dutyInstitution');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $search = request()->input('search');

        $dutyInstitutions = DutyInstitution::with('padalinys:id,shortname')->when(!request()->user()->isAdmin(), function ($query) {
            $query->where('padalinys_id', '=', request()->user()->padalinys()->id);
        })->when(!is_null($search), function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")->orWhere('alias', 'like', "%{$search}%");
        })->get();


        return Inertia::render('Admin/Contacts/Institutions/Index', [
            'dutyInstitutions' => $dutyInstitutions,
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
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function show(DutyInstitution $dutyInstitution)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function edit(DutyInstitution $dutyInstitution)
    {
        // dd($dutyInstitution);

        return Inertia::render('Admin/Contacts/Institutions/Edit', [
            'dutyInstitution' => $dutyInstitution,
            'duties' => $dutyInstitution->duties,
            'padaliniai' => Padalinys::orderBy('shortname_vu')->get()->map(function ($padalinys) {
                return [
                    'id' => $padalinys->id,
                    'shortname' => $padalinys->shortname,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DutyInstitution $dutyInstitution)
    {
        $dutyInstitution->update($request->only('name', 'shortname', 'alias', 'description', 'padalinys_id'));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DutyInstitution  $dutyInstitution
     * @return \Illuminate\Http\Response
     */
    public function destroy(DutyInstitution $dutyInstitution)
    {
        //
    }

    public function searchForInstitutions(Request $request)
    {
        $data = $request->collect()['data'];

        $institutions = DutyInstitution::when(!$request->user()->isAdmin(), function ($query) use ($request) {
            $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
            // check request for padaliniai, if not empty return only pages from request padaliniai
        })->where(function ($query) use ($data) {
            $query->where('name', 'like', "%{$data['name']}%")->orWhere('short_name', 'like', "%{$data['name']}%")->orWhere('alias', 'like', "%{$data['name']}%");
        })->get();

        $institutions = $institutions->map(function ($institution) {
            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'alias' => $institution->alias,
            ];
        });

        return back()->with('search_other', $institutions);
    }
}
