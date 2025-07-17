<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Exports\FormRegistrationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Tenant;
use App\Models\Training;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use App\Settings\FormSettings;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class FormController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Form::class);

        $indexer = new ModelIndexer(new Form);

        $forms = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return Inertia::render('Admin/Forms/IndexForm', [
            'forms' => $forms,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Form::class);

        return Inertia::render('Admin/Forms/CreateForm', [
            'assignableTenants' => GetTenantsForUpserts::execute('calendars.create.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFormRequest $request)
    {
        $form = new Form;

        $form->fill($request->only('name', 'description', 'path'));

        $form->tenant()->associate($request->tenant_id);

        $form->save();

        if ($request->training_id) {
            $training = Training::query()->find($request->training_id);

            $training->form()->associate($form);

            $training->save();
        }

        // Then, update or create the remaining form fields
        collect($request->only('form_fields')['form_fields'] ?? [])->each(function ($formField) use ($form) {
            unset($formField['id']);
            $form->formFields()->create($formField);
        });

        return redirect(request()->redirect_to ?? route('forms.index'))->with('success', 'Form created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Form $form)
    {
        $this->authorize('view', $form);

        $form->load('formFields', 'registrations.fieldResponses.formField');

        $registrations = $form->registrations;

        // If form is member registration form
        if (app(FormSettings::class)->member_registration_form_id === $form->id) {
            // Check which tenants should be shown
            $tenants = GetTenantsForUpserts::execute('forms.read.padalinys', $this->authorizer);

            if ($tenants->isEmpty()) {
                abort(403, 'No tenants to show.');
            }

            // Filter form registrations
            // 1. Find which fieldResponse has use_model_options and options_model Tenant
            $tenantField = $form->formFields->first(function ($field) {
                return $field->use_model_options && $field->options_model === Tenant::class;
            });

            // 2. Filter registrations that don't have a tenant field as in tenants
            $registrations = $form->registrations->filter(function ($registration) use ($tenantField, $tenants) {
                $tenantResponse = $registration->fieldResponses->first(function ($fieldResponse) use ($tenantField) {
                    return $fieldResponse->formField->id === $tenantField->id;
                });

                return $tenants->contains('id', $tenantResponse->response['value']);
            });
        }

        return Inertia::render('Admin/Forms/ShowForm', [
            'form' => $form,
            'registrations' => $registrations->values(),
        ]);
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
                'form_fields' => $form->formFields()->orderBy('order')->get()->map->toFullArray(),
                'registrations_count' => $form->registrations()->count(),
            ],
            'assignableTenants' => GetTenantsForUpserts::execute('calendars.update.padalinys', $this->authorizer),
            'fieldModelOptions' => [
                ['label' => 'Tenant', 'value' => Tenant::class],
            ],
            'fieldModelFields' => [
                ['label' => 'Pilnas pavadinimas', 'value' => 'fullname'],
                ['label' => 'Trumpas pavadinimas', 'value' => 'shortname'],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormRequest $request, Form $form)
    {
        $form->update($request->only('name', 'description', 'path'));

        $form->tenant()->associate($request->tenant_id);

        $form->save();

        // Update form fields
        // First, compare which form fields were removed
        $form->formFields->whereNotIn('id', collect($request->form_fields)->pluck('id'))->each->delete();

        if ($form->registrations->count() > 0) {
            collect($request->only('form_fields')['form_fields'])->each(function ($formField) {
                $formFieldFromDb = FormField::query()->find($formField['id']);

                // Don't update type
                $formFieldFromDb->update([
                    'label' => $formField['label'],
                    'description' => $formField['type'],
                    'options' => $formField['options'],
                    'is_required' => $formField['is_required'],
                    'default_value' => $formField['default_value'],
                    'placeholder' => $formField['placeholder'],
                    'order' => $formField['order'],
                ]);

            });
        }

        // Then, update or create the remaining form fields
        collect($request->only('form_fields')['form_fields'])->each(function ($formField) use ($form) {
            // In frontend, the ID is a 6-length string if the form field is new
            if (is_string($formField['id'])) {
                unset($formField['id']);
            }

            if (! isset($formField['id'])) {
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

    public function export(Form $form)
    {
        $this->authorize('update', $form);

        // slugify the form name up to 16 char, and add datetime
        $fileName = substr(Str::slug($form->name), 0, 20).'-'.now()->format('Y-m-d-H-i-s');

        return Excel::download(new FormRegistrationsExport($form), $fileName.'.xlsx');
    }
}
