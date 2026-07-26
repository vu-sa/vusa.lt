<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Exports\FormRegistrationsExport;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexFormRequest;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Training;
use App\Services\FormAccessService;
use App\Services\FormRegistrationVisibilityService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use App\Settings\FormSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;

class FormController extends AdminController
{
    use HasTanstackTables;

    /**
     * Attributes accepted from the client for a form field.
     *
     * @var array<int, string>
     */
    private const FORM_FIELD_ATTRIBUTES = [
        'label',
        'description',
        'type',
        'subtype',
        'options',
        'is_required',
        'default_value',
        'placeholder',
        'order',
        'use_model_options',
        'options_model',
        'options_model_field',
    ];

    public function __construct(
        public Authorizer $authorizer,
        private TanstackTableService $tableService,
        private FormAccessService $formAccess,
        private FormRegistrationVisibilityService $registrationVisibility,
    ) {}

    /**
     * Fields created in the browser carry a generated 'new-' prefixed id until they are persisted.
     */
    private static function isNewFormFieldId(mixed $id): bool
    {
        return $id === null || str_starts_with((string) $id, 'new-');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexFormRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Form::class);

        $query = Form::query()->with('tenant:id,shortname')->withCount('registrations');

        $searchableColumns = ['name', 'path'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
        );

        $query = $this->formAccess->applyIndexVisibility($query, $request->user());

        $forms = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        $sorting = $request->getSorting();
        $user = $request->user();

        return $this->inertiaResponse('Admin/Forms/IndexForm', [
            'forms' => [
                'data' => $forms->getCollection()
                    ->map(function ($form) use ($user) {
                        /** @var Form $form */
                        $registrationsCount = $this->registrationVisibility->isSharedRegistrationForm($form)
                            ? $this->registrationVisibility->count($form, $user)
                            : $form->registrations_count;

                        return [
                            ...$form->toFullArray(),
                            'registrations_count' => $registrationsCount,
                            'tenant' => [
                                'id' => $form->tenant->id,
                                'shortname' => $form->tenant->shortname,
                            ],
                            'can' => [
                                'view' => $user->can('view', $form),
                                'update' => $user->can('update', $form),
                                'delete' => $user->can('delete', $form),
                            ],
                        ];
                    }),
                'meta' => [
                    'total' => $forms->total(),
                    'per_page' => $forms->perPage(),
                    'current_page' => $forms->currentPage(),
                    'last_page' => $forms->lastPage(),
                    'from' => $forms->firstItem(),
                    'to' => $forms->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
            'can' => [
                'create' => $user->can('create', Form::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Form::class);

        return $this->inertiaResponse('Admin/Forms/CreateForm', [
            'assignableTenants' => GetTenantsForUpserts::execute('forms.create.padalinys', $this->authorizer),
            ...$this->fieldModelChoices(),
        ]);
    }

    /**
     * Models (and their label attributes) a form field can pull its options from.
     *
     * @return array{fieldModelOptions: array<int, array{label: string, value: string}>, fieldModelFields: array<int, array{label: string, value: string}>}
     */
    private function fieldModelChoices(): array
    {
        return [
            'fieldModelOptions' => [
                ['label' => __('forms.field_models.tenant'), 'value' => Tenant::class],
                ['label' => __('forms.field_models.institution'), 'value' => Institution::class],
            ],
            'fieldModelFields' => [
                ['label' => __('forms.field_model_attributes.fullname'), 'value' => 'fullname'],
                ['label' => __('forms.field_model_attributes.shortname'), 'value' => 'shortname'],
                ['label' => __('forms.field_model_attributes.name'), 'value' => 'name'],
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFormRequest $request)
    {
        $form = new Form;

        $form->fill($request->only('name', 'description', 'path', 'publish_time'));

        $form->tenant()->associate($request->tenant_id);

        $form->save();

        if ($request->training_id) {
            $training = Training::query()->find($request->training_id);

            $training->form()->associate($form);

            $training->save();
        }

        // Then, update or create the remaining form fields
        collect($request->only('form_fields')['form_fields'] ?? [])->each(function ($formField) use ($form) {
            $form->formFields()->create(collect($formField)->only(self::FORM_FIELD_ATTRIBUTES)->all());
        });

        return redirect(request()->redirect_to ?? route('forms.index'))->with('success', 'Form created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Form $form)
    {
        $this->handleAuthorization('view', $form);

        $form->load(['formFields' => fn ($query) => $query->orderBy('order')]);

        $registrations = $this->registrationVisibility
            ->query($form, $request->user())
            ->with('fieldResponses.formField')
            ->latest()
            ->get();

        // If form is student rep registration form, pass institutions for display
        $institutions = collect();
        if (app(FormSettings::class)->student_rep_registration_form_id === $form->id) {
            // Get all institutions that are referenced in the registrations
            $institutionField = $form->formFields->first(function ($field) {
                return $field->use_model_options && $field->options_model === Institution::class;
            });

            if ($institutionField) {
                $institutionIds = $registrations->flatMap(function ($registration) use ($institutionField) {
                    $response = $registration->fieldResponses->first(function ($fieldResponse) use ($institutionField) {
                        return $fieldResponse->formField->id === $institutionField->id;
                    });

                    return $response?->response['value'] ? [$response->response['value']] : [];
                })->unique();

                $institutions = Institution::whereIn('id', $institutionIds)->get(['id', 'name']);
            }
        }

        $canUpdate = $request->user()->can('update', $form);

        return $this->inertiaResponse('Admin/Forms/ShowForm', [
            'form' => $form,
            'registrations' => $registrations->values(),
            'institutions' => $institutions,
            'exportUrl' => $canUpdate ? route('forms.export', $form->id) : null,
            'publicUrl' => $this->publicFormUrl($form),
            'can' => [
                'update' => $canUpdate,
                'export' => $canUpdate,
            ],
        ]);
    }

    /**
     * Public URL of the registration form in the current locale, when it has a path.
     */
    private function publicFormUrl(Form $form): ?string
    {
        $locale = app()->getLocale();
        $path = $form->getTranslation('path', $locale);

        if (blank($path)) {
            return null;
        }

        return route('registrationPage', [
            'lang' => $locale,
            'registrationString' => $locale === 'lt' ? 'registracija' : 'registration',
            'registrationForm' => $path,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Form $form)
    {
        $this->handleAuthorization('update', $form);

        return $this->inertiaResponse('Admin/Forms/EditForm', [
            'form' => [
                ...$form->toFullArray(),
                'form_fields' => $form->formFields()->orderBy('order')->get()
                    ->map(function ($field) {
                        /** @var FormField $field */
                        return $field->toFullArray();
                    }),
                'registrations_count' => $form->registrations()->count(),
            ],
            'assignableTenants' => GetTenantsForUpserts::execute('forms.update.padalinys', $this->authorizer),
            ...$this->fieldModelChoices(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormRequest $request, Form $form)
    {
        $form->update($request->only('name', 'description', 'path', 'publish_time'));

        $form->tenant()->associate($request->tenant_id);

        $form->save();

        // Update form fields
        // First, compare which form fields were removed
        $form->formFields->whereNotIn('id', collect($request->form_fields)->pluck('id'))->each->delete();

        collect($request->only('form_fields')['form_fields'] ?? [])->each(function ($formField) use ($form) {
            $attributes = collect($formField)->only(self::FORM_FIELD_ATTRIBUTES)->all();

            // The frontend prefixes ids of not-yet-persisted fields with 'new-'.
            if (self::isNewFormFieldId($formField['id'] ?? null)) {
                $form->formFields()->create($attributes);

                return;
            }

            // Resolve through the relation so a crafted payload cannot reach another form's fields.
            $formFieldFromDb = $form->formFields()->find($formField['id']);

            abort_if($formFieldFromDb === null, 403, 'Form field does not belong to this form.');

            $formFieldFromDb->update($attributes);
        });

        return redirect()->back()->with('success', 'Form updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Form $form)
    {
        $this->handleAuthorization('delete', $form);

        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form deleted.');
    }

    public function export(Form $form)
    {
        $this->handleAuthorization('update', $form);

        // slugify the form name up to 16 char, and add datetime
        $fileName = substr(Str::slug($form->getTranslation('name', app()->getLocale())), 0, 20).'-'.now()->format('Y-m-d-H-i-s');

        return (new FormRegistrationsExport($form))->download($fileName.'.xlsx');
    }
}
