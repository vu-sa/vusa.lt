<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreProblemRequest;
use App\Http\Requests\UpdateProblemRequest;
use App\Models\Institution;
use App\Models\Problem;
use App\Models\ProblemCategory;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;

class ProblemController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->handleAuthorization('viewAny', Problem::class);

        $indexer = new ModelIndexer(new Problem);

        // Build callbacks array for filtering
        $callbacks = [
            // Eager load relationships
            fn ($query) => $query->with(['tenant', 'createdBy', 'responsibleUser', 'categories', 'institutions']),
        ];

        // Manual filtering - extract from indexer filters and handle before filterAllColumns()
        if ($indexer->filters) {
            // Status filter
            if (isset($indexer->filters['status']) && ! empty($indexer->filters['status'])) {
                $statusValues = $indexer->filters['status'];
                $callbacks[] = fn ($query) => $query->whereIn('status', $statusValues);
                unset($indexer->filters['status']);
            }

            // Category filter (relationship)
            if (isset($indexer->filters['category']) && ! empty($indexer->filters['category'])) {
                $categoryValues = $indexer->filters['category'];
                $callbacks[] = fn ($query) => $query->whereHas('categories', function ($q) use ($categoryValues) {
                    $q->whereIn('problem_categories.id', $categoryValues);
                });
                unset($indexer->filters['category']);
            }

            // Institution filter (relationship)
            if (isset($indexer->filters['institution']) && ! empty($indexer->filters['institution'])) {
                $institutionValues = $indexer->filters['institution'];
                $callbacks[] = fn ($query) => $query->whereHas('institutions', function ($q) use ($institutionValues) {
                    $q->whereIn('institutions.id', $institutionValues);
                });
                unset($indexer->filters['institution']);
            }
        }

        // "Show my problems" toggle (from direct request parameter)
        if ($request->boolean('show_my_problems')) {
            $callbacks[] = fn ($query) => $query->where('created_by', auth()->id());
        }

        $problems = $indexer
            ->setEloquentQuery($callbacks)
            ->filterAllColumns()
            ->sortAllColumns(['occurred_at' => 'descend'])
            ->builder->paginate(20);

        return $this->inertiaResponse('Admin/Problems/IndexProblem', [
            'problems' => $problems,
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'institutions' => Institution::select('id', 'name')->orderBy('name')->get()->map(fn ($institution) => $institution->toArray()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Problem::class);

        $tenants = GetTenantsForUpserts::execute('problems.create.padalinys', $this->authorizer);
        $tenantIds = collect($tenants)->pluck('id')->toArray();

        return $this->inertiaResponse('Admin/Problems/CreateProblem', [
            'tenants' => $tenants,
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'institutions' => Institution::select('id', 'name', 'tenant_id')
                ->whereIn('tenant_id', $tenantIds)
                ->orderBy('name')
                ->get()
                ->map(fn ($institution) => $institution->toArray()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProblemRequest $request)
    {
        $validated = $request->validated();
        $categories = $validated['categories'] ?? [];
        $institutions = $validated['institutions'] ?? [];
        unset($validated['categories'], $validated['institutions']);

        $problem = Problem::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if (! empty($categories)) {
            $problem->categories()->sync($categories);
        }

        if (! empty($institutions)) {
            $problem->institutions()->sync($institutions);
        }

        return $this->redirectToIndexWithSuccess('problems', trans_choice('messages.created', 0, ['model' => trans_choice('entities.problem.model', 1)]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Problem $problem)
    {
        $this->handleAuthorization('view', $problem);

        return $this->inertiaResponse('Admin/Problems/ShowProblem', [
            'problem' => [
                ...$problem->load([
                    'tenant',
                    'createdBy',
                    'responsibleUser',
                    'categories',
                    'institutions',
                    'activities.causer',
                ])->toFullArray(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Problem $problem)
    {
        $this->handleAuthorization('update', $problem);

        $problemData = $problem->load(['tenant', 'createdBy', 'responsibleUser', 'categories', 'institutions'])->toFullArray();
        $problemData['categories'] = $problem->categories->pluck('id')->toArray();
        $problemData['institutions'] = $problem->institutions->pluck('id')->toArray();

        $tenants = GetTenantsForUpserts::execute('problems.update.padalinys', $this->authorizer);
        $tenantIds = collect($tenants)->pluck('id')->toArray();

        return $this->inertiaResponse('Admin/Problems/EditProblem', [
            'problem' => $problemData,
            'tenants' => $tenants,
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'institutions' => Institution::select('id', 'name', 'tenant_id')
                ->whereIn('tenant_id', $tenantIds)
                ->orderBy('name')
                ->get()
                ->map(fn ($institution) => $institution->toArray()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProblemRequest $request, Problem $problem)
    {
        $validated = $request->validated();
        $categories = $validated['categories'] ?? [];
        $institutions = $validated['institutions'] ?? [];
        unset($validated['categories'], $validated['institutions']);

        $problem->update($validated);

        $problem->categories()->sync($categories);
        $problem->institutions()->sync($institutions);

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.problem.model', 1)]))->with('data', $problem->load(['categories', 'institutions']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem)
    {
        $this->handleAuthorization('delete', $problem);

        $problem->delete();

        return $this->redirectToIndexWithInfo('problems', trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.problem.model', 1)]));
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Problem $problem)
    {
        $this->handleAuthorization('restore', $problem);

        $problem->restore();

        return $this->redirectToIndexWithSuccess('problems', trans_choice('messages.restored', 0, ['model' => trans_choice('entities.problem.model', 1)]));
    }
}
