<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreInstitutionCheckInRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Services\CheckInService;
use App\Services\ModelAuthorizer;
use Illuminate\Http\RedirectResponse;

class CheckInActionController extends AdminController
{
    public function __construct(private CheckInService $service, public ModelAuthorizer $authorizer) {}

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

    public function destroy(InstitutionCheckIn $checkIn): RedirectResponse
    {
        $this->authorize('delete', $checkIn);

        $this->service->delete($checkIn);

        return back()->with('success', __('Check-in deleted.'));
    }
}
