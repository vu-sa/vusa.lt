<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Services\ModelAuthorizer as Authorizer;
use Inertia\Inertia;

class FormController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Form::class);

        return Inertia::render('Admin/Forms/IndexForm', [
            'forms' => Form::paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFormRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Form $form)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Form $form)
    {
        $this->authorize('update', $form);

        return Inertia::render('Admin/Forms/EditForm', [
            'form' => [
                ...$form->toFullArray(),
                'form_fields' => $form->formFields->map->toFullArray(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormRequest $request, Form $form)
    {
        $form->update($request->only('name', 'description', 'path'));

        // Update form fields
        // First, compare which form fields were removed

        $form->formFields->whereNotIn('id', collect($request->form_fields)->pluck('id'))->each->delete();

        // Then, update or create the remaining form fields
        collect($request->only('form_fields')['form_fields'])->each(function ($formField) use ($form) {

            // In frontend, the ID is a string if the form field is new
            if (is_string($formField['id'])) {
                unset($formField['id']);
            }

            if (!isset($formField['id'])) {
                $form->formFields()->create($formField);
                return;
            }

            $formFieldFromDb = FormField::query()->find($formField['id']);
            $formFieldFromDb->update($formField);
        });

        return redirect()->back()->with('success', 'Form updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Form $form)
    {
        $this->authorize('delete', $form);

        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }
}
