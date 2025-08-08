<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Programme;
use App\Models\ProgrammePart;
use App\Models\ProgrammeSection;
use App\Models\Training;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;

class TrainingController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Training::class);

        $indexer = new ModelIndexer(new Training);

        $trainings = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return $this->inertiaResponse('Admin/People/IndexTraining', [
            'trainings' => $trainings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Training::class);

        return $this->inertiaResponse('Admin/People/CreateTraining');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTrainingRequest $request)
    {
        $training = new Training($request->validated());

        $training->organizer_id = auth()->id();

        $training->save();

        $programme = new Programme([
            'title' => ['lt' => 'Programa'],
        ]);

        $programme->start_date = $training->start_time;

        $programme->save();

        $training->programmes()->attach($programme->id);

        return redirect()->route('trainings.index')->with('success', 'Mokymų šablonas sukurtas');
    }

    /**
     * Display the specified resource.
     */
    public function show(Training $training)
    {
        $training->load('activities', 'form', 'tenant', 'organizer', 'trainables', 'tasks', 'institution');

        $training->load('programmes.days.elements');

        return $this->inertiaResponse('Admin/People/ShowTraining', [
            'training' => [
                ...$training->toArray(),
                'programmes' => $training->programmes->map(function ($programme) {
                    return [
                        ...$programme->toArray(),
                        'days' => $programme->days->map(function ($day) {
                            return [
                                ...$day->toArray(),
                                'elements' => $day->elements->map(function ($element) {
                                    if ($element->elementable instanceof ProgrammeSection) {
                                        return [
                                            ...$element->elementable->toArray(),
                                            'blocks' => $element->elementable->blocks->map(function ($block) {
                                                return [
                                                    ...$block->toArray(),
                                                    'parts' => $block->parts->map(function ($part) {
                                                        return [
                                                            ...$part->toArray(),
                                                            'type' => 'part',
                                                        ];
                                                    }),
                                                    'type' => 'block',
                                                ];
                                            }),
                                            'type' => 'section',
                                        ];
                                    }

                                    if ($element->elementable instanceof ProgrammePart) {
                                        return [
                                            ...$element->elementable->toArray(),
                                            'type' => 'part',
                                        ];
                                    }
                                }),
                                'type' => 'day',
                            ];
                        }),
                    ];
                }),
            ],
            'userIsRegistered' => $training->form?->registrations->contains('user_id', auth()->id()),
            'userCanRegister' => auth()->user()->allAvailableTrainings()->contains('id', $training->id),
            'registeredUserCount' => $training->form?->registrations->count(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Training $training)
    {
        $this->handleAuthorization('update', $training);

        $training->load('form', 'tenant', 'organizer', 'trainables', 'tasks');

        return $this->inertiaResponse('Admin/People/EditTraining', [

            'training' => [
                ...$training->toFullArray(),
                'form' => [
                    ...($training->form ? $training->form->toFullArray() : []),
                    'form_fields' => $training->form?->formFields()->orderBy('order')->get()
                        ->map(function ($field) {
                            /** @var \App\Models\FormField $field */
                            return $field->toFullArray();
                        }),
                ],
                'tasks' => $training->tasks->map->toFullArray(),
                'programme' => collect([
                    $training->load('programmes.days.elements.elementable')->programmes->map(function ($programme) {
                        return [
                            ...$programme->toFullArray(),
                            'days' => $programme->days->map(function ($day) {
                                return [
                                    ...$day->toFullArray(),
                                    'type' => 'day',
                                    'elements' => $day->elements->map(function ($element) {
                                        // check if elementable is a section or part
                                        $elementable = $element->elementable;

                                        if ($elementable instanceof ProgrammeSection) {
                                            return [
                                                ...$elementable->toFullArray(),
                                                'blocks' => $elementable->blocks->map(function ($block) {
                                                    return [
                                                        ...$block->toFullArray(),
                                                        'parts' => $block->parts->map(function ($part) {
                                                            return [
                                                                ...$part->toFullArray(),
                                                                'type' => 'part',
                                                            ];
                                                        }),
                                                        'type' => 'block',
                                                    ];
                                                }),
                                                'type' => 'section',
                                            ];
                                        }

                                        if ($elementable instanceof ProgrammePart) {
                                            return [
                                                ...$elementable->toFullArray(),
                                                'type' => 'part',
                                            ];
                                        }
                                    }),
                                ];
                            }),
                        ];
                    })->first(),
                ])->first(),
            ],
            'trainableTypes' => [
                User::class => ['type' => User::class, 'name' => 'Narys', 'values' => User::query()->get(['id', 'name'])],
                Duty::class => ['type' => Duty::class, 'name' => 'Pareiga', 'values' => Duty::query()->get(['id', 'name'])],
                Institution::class => ['type' => Institution::class, 'name' => 'Institucija', 'values' => Institution::query()->get(['id', 'name'])],
                Membership::class => ['type' => Membership::class, 'name' => 'Narystė', 'values' => Membership::query()->get(['id', 'name'])],
                // TODO: Implement later (can't because id isn't ulid)
                /* Type::class => ['type' => Type::class, 'name' => 'Tipas', 'values' => Type::query()->where('model_type', 'App\Models\Duty')->orWhere('model_type', 'App\Models\Institution')->get(['id', 'title'])], */
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTrainingRequest $request, Training $training)
    {
        $this->handleAuthorization('update', $training);

        $training->update($request->except('trainables', 'tasks'));

        $training->trainables()->delete();

        $training->trainables()->createMany($request->trainables);

        $training->tasks()->delete();

        $training->tasks()->createMany($request->tasks);

        return back()->with('success', 'Mokymų šablonas atnaujintas');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Training $training)
    {
        $this->handleAuthorization('delete', $training);

        $training->delete();

        return redirect()->route('trainings.index')->with('success', 'Mokymų šablonas ištrintas');
    }

    public function showRegistration(Training $training)
    {
        $training->load('form');

        if ($training->form?->registrations->contains('user_id', auth()->id())) {
            return redirect()->route('trainings.show', $training)->with('success', 'Jūs jau užsiregistravote į šiuos mokymus!');
        }

        $processedForm = [
            ...$training->form->toArray(),
            'form_fields' => $training->form->formFields->map(function ($field) {
                $options = $field->options;

                if ($field->use_model_options) {
                    $options = $field->options_model::all()->map(function ($model) use ($field) {
                        return [
                            'value' => $model->id,
                            'label' => $model->{$field->options_model_field},
                        ];
                    });
                }

                return [
                    ...$field->toArray(),
                    'options' => $options,
                ];
            }),
        ];

        // NOTE: Form formation repeated in PublicPageController:349
        return $this->inertiaResponse('Admin/People/ShowTrainingRegistration', [
            'training' => [
                ...$training->toArray(),
                'form' => $processedForm,
            ],
        ]);
    }
}
