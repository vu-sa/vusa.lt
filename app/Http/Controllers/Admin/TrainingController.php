<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Models\Training;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Inertia\Inertia;

class TrainingController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Training::class);

        $indexer = new ModelIndexer(new Training);

        $trainings = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return Inertia::render('Admin/People/IndexTraining', [
            'trainings' => $trainings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Training::class);

        return Inertia::render('Admin/People/CreateTraining');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTrainingRequest $request)
    {
        $training = new Training($request->validated());

        $training->organizer_id = auth()->id();

        $training->save();

        return redirect()->route('trainings.index')->with('success', 'Mokymų šablonas sukurtas');
    }

    /**
     * Display the specified resource.
     */
    public function show(Training $training)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Training $training)
    {
        $training->load('form', 'tenant', 'organizer');

        return Inertia::render('Admin/People/EditTraining', [

            'training' => [
                ...$training->toFullArray(),
                'form' => [
                    ...($training->form ? $training->form->toFullArray() : []),
                    'form_fields' => $training->form?->formFields()->orderBy('order')->get()->map->toFullArray(),
                ],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTrainingRequest $request, Training $training)
    {
        $training->update($request->validated());

        return back()->with('success', 'Mokymų šablonas atnaujintas');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Training $training)
    {
        $training->delete();

        return redirect()->route('trainings.index')->with('success', 'Mokymų šablonas ištrintas');
    }
}
