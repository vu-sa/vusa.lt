<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreInstitutionCheckInRequest;
use App\Models\Institution;
use App\Services\CheckInService;
use App\Services\ModelAuthorizer;
use Illuminate\Http\RedirectResponse;

class InstitutionCheckInController extends AdminController
{
    public function __construct(private CheckInService $service, public ModelAuthorizer $authorizer) {}

    /**
     * Store a new check-in for an institution.
     */
    public function store(StoreInstitutionCheckInRequest $request, Institution $institution): RedirectResponse
    {
        $this->service->create(
            $request->user(),
            $institution,
            $request->date('start_date'),
            $request->date('end_date'),
            $request->filled('note') ? (string) $request->input('note') : null
        );

        return back()->with('success', __('Check-in created.'));
    }

    /**
     * Delete all active check-ins for an institution.
     */
    public function destroyActive(Institution $institution): RedirectResponse
    {
        $checkIns = $institution->activeCheckIns()->get();

        if ($checkIns->isEmpty()) {
            return back()->with('info', __('No active check-ins to delete.'));
        }

        $this->authorize('delete', $checkIns->first());

        $this->service->deleteActive($institution);

        return back()->with('success', __('Active check-ins deleted successfully.'));
    }
}
