<?php

namespace App\Http\Controllers\Admin;

use App\Models\Type;
use App\Http\Controllers\Controller as Controller;
use App\Services\SharepointAppGraph;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Type::class, 'type');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Admin/ModelMeta/IndexTypes', [
            'types' => Type::paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/ModelMeta/CreateType', [
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'model_type' => 'required',
            'parent_id' => 'nullable|exists:types,id|different:id',
        ]);

        Type::create($request->only('title', 'model_type', 'description', 'parent_id'));

        return redirect()->route('types.index')
            ->with('success', 'Turinio tipas sukurtas sėkmingai.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function show(Type $type)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function edit(Type $type)
    {
        $sharepointFiles = [];

        if ($type->documents->count() > 0) {
            $graph = new SharepointAppGraph();
        
            $sharepointFiles = $graph->collectSharepointFiles($type->documents);
        }
        
        return Inertia::render('Admin/ModelMeta/EditType', [
            'contentType' => $type->toArray() + ['sharepointFiles' => $sharepointFiles],
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Type $type)
    {
        $request->validate([
            'title' => 'required',
            'model_type' => 'required',
            'parent_id' => 'nullable|exists:types,id|different:id',
        ]);

        $type->update($request->only('title', 'model_type', 'description', 'parent_id'));

        return redirect()->route('types.index')->with('success', 'Turinio tipas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function destroy(Type $type)
    {
        throw new Exception("Šiuo metu negalima trinti tipų, pavojinga...", 1);
        
        DB::transaction(function () use ($type) {
            // delete typeables
            DB::table('typeables')->where('type_id', $type->id)->delete();
            // delete type
            $type->delete();
        });

        return back()->with('success', 'Tipas ištrintas');
    }
}
