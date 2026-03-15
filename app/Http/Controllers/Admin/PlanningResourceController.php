<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StorePlanningResourceRequest;
use App\Models\PlanningResource;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanningResourceController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Abort unless the current user has coordinator-level write permission.
     */
    private function authorizeCoordinator(): void
    {
        abort_unless(
            $this->authorizer->forUser(auth()->user())->check('planningProcesses.update.padalinys'),
            403
        );
    }

    /**
     * Store a new planning resource.
     */
    public function store(StorePlanningResourceRequest $request): RedirectResponse
    {
        $this->authorizeCoordinator();

        $validated = $request->validated();

        $resource = PlanningResource::create([
            'academic_year_start' => $validated['academic_year_start'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'category' => $validated['category'] ?? null,
            'sort_order' => 0,
        ]);

        if ($validated['type'] === 'file' && $request->hasFile('file')) {
            $resource
                ->addMediaFromRequest('file')
                ->toMediaCollection('resource_file');
        }

        return back()->with('success', __('planning.resource_created'));
    }

    /**
     * Update an existing planning resource.
     */
    public function update(Request $request, PlanningResource $planningResource): RedirectResponse
    {
        $this->authorizeCoordinator();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $planningResource->update($validated);

        return back()->with('success', __('planning.resource_updated'));
    }

    /**
     * Delete a planning resource.
     */
    public function destroy(PlanningResource $planningResource): RedirectResponse
    {
        $this->authorizeCoordinator();

        $planningResource->clearMediaCollection('resource_file');
        $planningResource->delete();

        return back()->with('success', __('planning.resource_deleted'));
    }
}
