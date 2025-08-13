<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreInstitutionCheckInRequest;
use App\Http\Requests\ConfirmInstitutionCheckInRequest;
use App\Http\Requests\DisputeInstitutionCheckInRequest;
use App\Http\Requests\ResolveInstitutionCheckInRequest;
use App\Http\Requests\SuppressInstitutionCheckInRequest;
use App\Http\Requests\UnsuppressInstitutionCheckInRequest;
use App\Http\Requests\WithdrawInstitutionCheckInRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionCheckInVerification;
use App\Services\CheckInService;
use Illuminate\Http\RedirectResponse;

class CheckInActionController extends AdminController
{
    public function __construct(private CheckInService $service)
    {
    }

    public function store(StoreInstitutionCheckInRequest $request, Institution $institution): RedirectResponse
    {
        $until = now()->copy()->addDays((int) $request->integer('duration_days'));
        $this->service->create(
            $request->user(),
            $institution,
            $until,
            (string) $request->input('confidence'),
            $request->filled('note') ? (string) $request->input('note') : null,
            (string) $request->input('mode', 'blackout')
        );

        return back()->with('success', __('Check-in created.'));
    }

    public function withdraw(WithdrawInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): RedirectResponse
    {
        $this->service->withdraw($checkIn);
        return back()->with('success', __('Check-in withdrawn.'));
    }

    public function confirm(ConfirmInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): RedirectResponse
    {
        // Create verification record idempotently and increment only when created
        $verification = InstitutionCheckInVerification::firstOrCreate([
            'check_in_id' => $checkIn->id,
            'user_id' => $request->user()->id,
        ]);
        if ($verification->wasRecentlyCreated) {
            $this->service->confirm($checkIn);
        }
        return back()->with('success', __('Check-in confirmed.'));
    }

    public function dispute(DisputeInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): RedirectResponse
    {
    $this->service->dispute($checkIn, $request->user(), $request->string('reason')->toString() ?: null);
        return back()->with('success', __('Check-in disputed.'));
    }

    public function resolve(ResolveInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): RedirectResponse
    {
    $resolution = $request->string('resolution')->toString(); // 'withdraw' | 'keep'
    $this->service->resolve($checkIn, $resolution);
        return back()->with('success', __('Check-in resolved.'));
    }

    public function suppress(SuppressInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): RedirectResponse
    {
    $this->service->suppress($checkIn, $request->user(), $request->string('reason')->toString());
        return back()->with('success', __('Check-in suppressed.'));
    }

    public function unsuppress(UnsuppressInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): RedirectResponse
    {
        $this->service->unsuppress($checkIn);
        return back()->with('success', __('Check-in unsuppressed.'));
    }
}
