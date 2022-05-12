<?php

namespace App\Http\Controllers\Admin;

use App\Models\Duty;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;

class DutyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $duties = Duty::paginate(20);

        return Inertia::render('Admin/Contacts/Duties/Index', [
            'duties' => $duties,
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
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function show(Duty $duty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function edit(Duty $duty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Duty $duty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function destroy(Duty $duty)
    {
        //
    }

    public function searchForDuties(Request $request)
    {
        $data = $request->collect()['data'];

        $duties = Duty::where('name', 'like', "%{$data['name']}%")->get();

        $duties = $duties->map(function ($duty) {
            return [
                'id' => $duty->id,
                'name' => $duty->name,
                'institution' => $duty->institution->alias,
            ];
        });

        return back()->with('search_other', $duties);
    }
}
