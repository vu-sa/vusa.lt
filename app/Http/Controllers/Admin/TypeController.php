<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ResourceController;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TypeController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Type::class, $this->authorizer]);

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
        $this->authorize('create', [Type::class, $this->authorizer]);

        return Inertia::render('Admin/ModelMeta/CreateType', [
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Type::class, $this->authorizer]);

        $request->validate([
            'title' => 'required',
            'model_type' => 'string|required',
            'parent_id' => 'nullable|exists:types,id|different:id',
            'slug' => 'nullable|string',
        ]);

        // TODO: somehow check if model_type is valid and allowed

        Type::create($request->only('title', 'model_type', 'description', 'parent_id', 'slug'));

        return redirect()->route('types.index')
            ->with('success', 'Turinio tipas sukurtas sėkmingai.');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Type $type)
    {
        $this->authorize('view', [Type::class, $type, $this->authorizer]);

        return Inertia::render('Admin/ModelMeta/ShowType', [
            'contentType' => $type->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Type $type)
    {
        $this->authorize('update', [Type::class, $type, $this->authorizer]);

        $modelType = Str::of($type->model_type)->afterLast('\\')->lower()->plural()->toString();

        return Inertia::render('Admin/ModelMeta/EditType', [
            'contentType' => $type->load($modelType),
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
            'sharepointPath' => $type->sharepoint_path(),
            'allModelsFromModelType' => $type->allModelsFromModelType()->toArray(),
            'modelType' => $modelType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Type $type)
    {
        $this->authorize('update', [Type::class, $type, $this->authorizer]);

        $request->validate([
            'title' => 'required',
            'model_type' => 'required',
            'parent_id' => 'nullable|exists:types,id|different:id',
        ]);

        $type->update($request->only('title', 'model_type', 'description', 'parent_id'));

        $modelType = Str::of($request->model_type)->afterLast('\\')->lower()->plural()->toString();

        $type->$modelType()->sync($request->input($modelType, []));

        return redirect()->route('types.index')->with('success', 'Turinio tipas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Type $type)
    {
        $this->authorize('delete', [Type::class, $type, $this->authorizer]);

        DB::transaction(function () use ($type) {
            // delete typeables
            DB::table('typeables')->where('type_id', $type->id)->delete();
            // delete type
            $type->delete();
        });

        return back()->with('success', 'Tipas ištrintas');
    }
}
