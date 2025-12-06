<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexInstitutionRequest;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest; // Create this request class
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Type;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexInstitutionRequest $request): \Inertia\Response
    {
        $this->handleAuthorization('viewAny', Institution::class);

        // Build base query with eager loading
        $query = Institution::query()->with(['meetings' => fn ($query) => $query->orderBy('start_time'), 'tenant', 'types']);

        // Define searchable columns
        $searchableColumns = ['name', 'alias', 'email', 'tenant.name'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'tenantRelation' => 'tenant',
                'permission' => 'institutions.read.padalinys',
                'applySortBeforePagination' => true, // Ensure sorting is applied before pagination
            ]
        );

        // Paginate results
        $institutions = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        // Get institution types for filtering
        $types = Type::where('model_type', Institution::class)->get();

        // Get the sorting state using the custom method to ensure consistent parsing
        $sorting = $request->getSorting();

        // Return response with all necessary data
        return $this->inertiaResponse('Admin/People/IndexInstitution', [
            'data' => $institutions->items(),
            'meta' => [
                'total' => $institutions->total(),
                'per_page' => $institutions->perPage(),
                'current_page' => $institutions->currentPage(),
                'last_page' => $institutions->lastPage(),
                'from' => $institutions->firstItem(),
                'to' => $institutions->lastItem(),
            ],
            'types' => $types,
            'filters' => $request->getFilters(),
            'sorting' => $sorting, // Pass properly parsed sorting state to frontend
            'initialSorting' => $sorting, // Add initial sorting to persist state on first load
            'showDeleted' => $request->boolean('showDeleted', false), // Add showDeleted state
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Institution::class);

        Inertia::share('seo.title', 'Nauja institucija');

        return $this->inertiaResponse('Admin/People/CreateInstitution', [
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.create.padalinys', $this->authorizer),
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInstitutionRequest $request)
    {
        $institution = new Institution;

        $institution->fill($request->safe()->except('types'))->save();

        $institution->types()->sync($request->types);

        return redirect()->route('institutions.index')->with('success', 'Institucija sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Institution $institution)
    {
        $this->handleAuthorization('view', $institution);

        // TODO: only show current_users
        $institution->load('tenant', 'duties.current_users')->load(['meetings' => function ($query) {
            $query->with('tasks', 'comments', 'files', 'institutions.types')->orderBy('start_time', 'asc');
        }])->load('activities.causer');

        // Inertia::share('layout.navBackground', $institution->image_url ?? null);

        return $this->inertiaResponse('Admin/People/ShowInstitution', [
            'institution' => [
                ...$institution->toArray(),
                'current_users' => $institution->duties->load('current_users')->pluck('current_users')->flatten()->unique('id')->values(),
                'managers' => $institution->managers(),
                'relatedInstitutions' => $institution->related_institution_relationshipables(),
                'sharepointPath' => $institution->tenant ? $institution->sharepoint_path() : null,
                'lastMeeting' => $institution->lastMeeting(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Institution $institution)
    {
        $this->handleAuthorization('update', $institution);

        $institution->load('types')->load(['duties' => function ($query) {
            $query->with('current_users')->orderBy('order', 'asc');
        }]);

        Inertia::share('seo.title', $institution->name);

        return $this->inertiaResponse('Admin/People/EditInstitution', [
            'institution' => [
                ...$institution->toFullArray(),
                'types' => $institution->types->pluck('id'),
            ],
            'institutionTypes' => Type::where('model_type', Institution::class)->get(),
            'assignableTenants' => GetTenantsForUpserts::execute('institutions.update.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        $institution->fill($request->safe()->except('tenant_id', 'types'));

        // check if super admin, then update tenant_id
        if (auth()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $institution->fill($request->safe()->only('tenant_id'));
        }

        $institution->save();

        // get only types id
        $institution->types()->sync($request->types);

        return back()->with('success', 'Institucija sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Institution $institution)
    {
        $this->handleAuthorization('delete', $institution);

        // check if auth user is from this institution
        if (auth()->user()->institutions->contains($institution)) {
            return back()->with('error', 'Negalima ištrinti institucijos, kurioje esate!');
        }

        $institution->delete();

        return redirect()->route('institutions.index')->with('info', 'Institucija sėkmingai ištrinta!');
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Institution $institution)
    {
        $this->handleAuthorization('restore', $institution);

        $institution->restore();

        return back()->with('success', 'Institucija sėkmingai atkurta!');
    }

    /**
     * reorderDuties
     * Duties are ordered in the frontend array by the user. The order is saved in the database
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorderDuties(Request $request)
    {
        foreach ($request->duties as $duty) {
            $dutyModel = Duty::find($duty['id']);
            $dutyModel->order = $duty['order'];
            $dutyModel->save();
        }

        return back()->with('success', 'Pareigų tvarka sėkmingai atnaujinta!');
    }
}
