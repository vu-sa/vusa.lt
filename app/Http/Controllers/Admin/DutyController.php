<?php

namespace App\Http\Controllers\Admin;

use App\Models\Duty;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Inertia\Inertia;
use stdClass;

class DutyController extends Controller
{
    
    public function __construct()
    {
        $this->authorizeResource(Duty::class, 'duty');
    }
    
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
        $attributes = new stdClass;
        $attributes->en = new stdClass;
        $attributes->en->name = '';
        $attributes->en->description = '';
        $attributes = json_encode($attributes);

        if (!empty($duty->attributes)) {
            $attributes = $duty->attributes;
        }

        // dd($attributes);

        return Inertia::render('Admin/Contacts/Duties/Edit', [
            'duty' => [
                'id' => $duty->id,
                'name' => $duty->name,
                'description' => $duty->description,
                'type' => $duty->type,
                'institution' => $duty->institution,
                'email' => $duty->email,
                'attributes' => json_decode($attributes),
                'places_to_occupy' => $duty->places_to_occupy,
                'users' => $duty->users,
            ]
        ]);
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
        $duty->update($request->only('name', 'description', 'email', 'attributes'));

        return back();
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
