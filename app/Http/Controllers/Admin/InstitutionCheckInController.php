<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreInstitutionCheckInRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\Services\CheckInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class InstitutionCheckInController extends AdminController
{
    public function __construct(private readonly CheckInService $service) {}

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
        /** @var User $user */
        $user = Auth::user();
        $canDeleteAll = $user->can('deleteAll', [InstitutionCheckIn::class, $institution]);
        $checkIns = $institution->activeCheckIns()
            ->when(! $canDeleteAll, fn ($query) => $query->where('user_id', $user->id))
            ->get();

        if ($checkIns->isEmpty()) {
            abort_if(! $canDeleteAll && $institution->activeCheckIns()->exists(), 403);

            if (! $canDeleteAll) {
                $this->authorize('create', [InstitutionCheckIn::class, $institution]);
            }

            return back()->with('info', __('No active check-ins to delete.'));
        }

        $this->authorize('delete', $checkIns->first());

        $this->service->deleteActive($institution, $canDeleteAll ? null : $user);

        return back()->with('success', __('Active check-ins deleted successfully.'));
    }
}
