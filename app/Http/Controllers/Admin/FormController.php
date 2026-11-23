<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildFormIndexQuery;
use App\Actions\GetTenantsForUpserts;
use App\Enums\FormOptionSource;
use App\Exports\FormRegistrationsExport;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexFormRequest;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Services\FormAccessService;
use App\Services\FormRegistrationVisibilityService;
use App\Services\Forms\FormPresenter;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\PostCreateRedirect;
use App\Services\TanstackTableService;
use App\Settings\FormSettings;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    /**
     * Attributes accepted from the client for a form field.
     *
     * @var array<int, string>
     */
    private const array FORM_FIELD_ATTRIBUTES = [
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
        'option_source',
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
    #[Authorize('viewAny', Form::class)]
    public function index(IndexFormRequest $request): Response
    {
        $user = $request->user();

        $query = BuildFormIndexQuery::execute($request, $user, $this->formAccess, $this->tableService);

        $searchableColumns = ['name', 'path'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
        );

        $deletedCount = $this->getTrashedCount($query);

        // Trash view only: lets the table say why permanent deletion is refused.
        $query = $this->withForceDeleteBlockers($query, $request, ['registrations']);

        $forms = $query->paginate($request->getPerPage())
            ->withQueryString();

        $this->appendForceDeleteBlockedReason($forms->getCollection(), $request);

        $sorting = $request->getSorting();

        return Inertia::render('Admin/Forms/IndexForm', [
            'forms' => [
                'data' => FormPresenter::tableRows($forms->getCollection(), $user, $this->registrationVisibility),
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
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
            'can' => [
                'create' => $user->can('create', Form::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', Form::class)]
    public function create(Request $request)
    {
        return Inertia::render('Admin/Forms/CreateForm', [
            'assignableTenants' => GetTenantsForUpserts::execute($request->user(), 'forms.create.padalinys', $this->authorizer),
            ...$this->fieldModelChoices(),
        ]);
    }

    /**
     * Typed option sources a form field can use.
     *
     * @return array{fieldModelOptions: array<int, array{label: string, value: string}>}
     */
    private function fieldModelChoices(): array
    {
        return [
            'fieldModelOptions' => [
                ...collect(FormOptionSource::cases())
                    ->map(fn (FormOptionSource $source) => ['label' => __($source->labelKey()), 'value' => $source->value])
                    ->all(),
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', Form::class)]
    public function store(StoreFormRequest $request, PostCreateRedirect $redirect)
    {
        $form = new Form;

        $form->fill($request->safe()->only('name', 'description', 'path', 'publish_time'));

        $form->tenant()->associate($request->validated('tenant_id'));

        DB::transaction(function () use ($form, $request): void {
            $form->save();

            collect($request->safe()->only('form_fields')['form_fields'] ?? [])->each(function ($formField) use ($form): void {
                $form->formFields()->create(collect($formField)->only(self::FORM_FIELD_ATTRIBUTES)->all());
            });
        });

        return redirect($redirect->url($request->user(), $form, 'forms.show', 'view', 'forms.index'))
            ->with('success', $this->entityMessage('created', 'form'));
    }

    /**
     * Display the specified resource.
     */
    #[Authorize('view', 'form')]
    public function show(Request $request, Form $form): Response
    {
        $form->load(['formFields' => fn ($query) => $query->orderBy('order')]);

        $registrations = $this->registrationVisibility
            ->query($form, $request->user())
            ->with('fieldResponses')
            ->latest()
            ->get();

        // If form is student rep registration form, pass institutions for display
        $institutions = collect();
        if (app(FormSettings::class)->student_rep_registration_form_id === $form->id) {
            // Get all institutions that are referenced in the registrations
            $fields = $form->formFields->keyBy('id');
            $institutionField = $fields->first(fn (FormField $field) => $field->resolvedOptionSource() === FormOptionSource::Institution);

            if ($institutionField) {
                $institutionIds = $registrations->flatMap(function ($registration) use ($fields, $institutionField): array {
                    $response = $registration->fieldResponses->first(fn ($fieldResponse) => $fields->get($fieldResponse->form_field_id)?->id === $institutionField->id);

                    return $response?->response['value'] ? [$response->response['value']] : [];
                })->unique();

                $institutions = Institution::whereIn('id', $institutionIds)->get(['id', 'name']);
            }
        }

        $canUpdate = $request->user()->can('update', $form);

        return Inertia::render('Admin/Forms/ShowForm', [
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

        return LocalizedRouteSlugs::route('registrationPage', ['registrationForm' => $path], $locale);
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'form')]
    public function edit(Request $request, Form $form)
    {
        return Inertia::render('Admin/Forms/EditForm', [
            'form' => [
                ...$form->toFullArray(),
                'form_fields' => $form->formFields()->orderBy('order')->get()
                    ->map(function ($field) {
                        /** @var FormField $field */
                        return $field->toFullArray();
                    }),
                'registrations_count' => $form->registrations()->count(),
            ],
            'assignableTenants' => GetTenantsForUpserts::execute($request->user(), 'forms.update.padalinys', $this->authorizer),
            ...$this->fieldModelChoices(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'form')]
    public function update(UpdateFormRequest $request, Form $form): RedirectResponse
    {
        DB::transaction(function () use ($request, $form): void {
            $lockedForm = Form::query()->lockForUpdate()->findOrFail($form->id);
            // The form may have moved to another tenant since route authorization.
            Gate::forUser($request->user())->authorize('update', $lockedForm);

            $this->saveFormAndFields($lockedForm, $request);
        });

        return redirect()->back()->with('success', $this->entityMessage('updated', 'form'));
    }

    private function saveFormAndFields(Form $form, UpdateFormRequest $request): void
    {
        $submittedFields = collect($request->validated('form_fields', []));
        $submittedIds = $submittedFields->pluck('id')->reject(self::isNewFormFieldId(...))->map(fn (int|string $id): int => (int) $id);
        $existingFields = $form->formFields()->lockForUpdate()->get()->keyBy('id');

        abort_if($submittedIds->diff($existingFields->keys())->isNotEmpty(), 403, 'Form field does not belong to this form.');

        $form->fill($request->safe()->only('name', 'description', 'path', 'publish_time'));
        $form->tenant()->associate($request->validated('tenant_id'));
        $form->save();

        $existingFields->except($submittedIds->all())->each->delete();

        foreach ($submittedFields as $field) {
            $attributes = collect($field)->only(self::FORM_FIELD_ATTRIBUTES)->all();

            if (self::isNewFormFieldId($field['id'] ?? null)) {
                $form->formFields()->create($attributes);

                continue;
            }

            $existingFields->get((int) $field['id'])->update($attributes);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'form')]
    public function destroy(Form $form)
    {
        $form->delete();

        return redirect()->route('forms.index')->with('success', $this->entityMessage('deleted', 'form'));
    }

    #[Authorize('update', 'form')]
    public function export(Request $request, Form $form)
    {
        $fileName = substr(Str::slug($form->getTranslation('name', app()->getLocale())), 0, 20).'-'.now()->format('Y-m-d-H-i-s');

        return new FormRegistrationsExport($form, $request->user())->download($fileName.'.xlsx');
    }

    #[Authorize('restore', 'form')]
    public function restore(Form $form): RedirectResponse
    {
        return $this->restoreModel($form);
    }

    #[Authorize('forceDelete', 'form')]
    public function forceDelete(Form $form): RedirectResponse
    {
        return $this->forceDeleteModel($form);
    }
}
