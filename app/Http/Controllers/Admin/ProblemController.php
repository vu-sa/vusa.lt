<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreProblemRequest;
use App\Http\Requests\UpdateProblemRequest;
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
            fn ($query) => $query->with(['tenant', 'createdBy', 'responsibleUser', 'categories']),
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
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Problem::class);

        return $this->inertiaResponse('Admin/Problems/CreateProblem', [
            'tenants' => GetTenantsForUpserts::execute('problems.create.padalinys', $this->authorizer),
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProblemRequest $request)
    {
        $validated = $request->validated();
        $categories = $validated['categories'] ?? [];
        unset($validated['categories']);

        $problem = Problem::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if (! empty($categories)) {
            $problem->categories()->sync($categories);
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

        $problemData = $problem->load(['tenant', 'createdBy', 'responsibleUser', 'categories'])->toFullArray();
        $problemData['categories'] = $problem->categories->pluck('id')->toArray();

        return $this->inertiaResponse('Admin/Problems/EditProblem', [
            'problem' => $problemData,
            'tenants' => GetTenantsForUpserts::execute('problems.update.padalinys', $this->authorizer),
            'categories' => ProblemCategory::orderBy('slug')->get()->map(fn ($category) => $category->toArray()),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProblemRequest $request, Problem $problem)
    {
        $validated = $request->validated();
        $categories = $validated['categories'] ?? [];
        unset($validated['categories']);

        $problem->update($validated);

        $problem->categories()->sync($categories);

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.problem.model', 1)]))->with('data', $problem->load('categories'));
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
