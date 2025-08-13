<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmInstitutionCheckInRequest;
use App\Http\Requests\DisputeInstitutionCheckInRequest;
use App\Http\Requests\ResolveInstitutionCheckInRequest;
use App\Http\Requests\StoreInstitutionCheckInRequest;
use App\Http\Requests\SuppressInstitutionCheckInRequest;
use App\Http\Requests\UnsuppressInstitutionCheckInRequest;
use App\Http\Requests\WithdrawInstitutionCheckInRequest;
use App\Http\Requests\FlagInstitutionCheckInRequest;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionCheckInVerification;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionCheckInController extends Controller
{
    public function __construct(private CheckInService $service)
    {
    }

    public function store(StoreInstitutionCheckInRequest $request, Institution $institution): JsonResponse
    {
        $until = now()->copy()->addDays((int) $request->integer('duration_days'));
        $checkIn = $this->service->create(
            $request->user(),
            $institution,
            $until,
            (string) $request->input('confidence'),
            $request->filled('note') ? (string) $request->input('note') : null,
            (string) $request->input('mode', 'blackout')
        );

        return response()->json(['data' => $checkIn->fresh()], 201);
    }

    public function confirm(ConfirmInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
        // Create verification record idempotently and increment count only when created
        $verification = InstitutionCheckInVerification::firstOrCreate([
            'check_in_id' => $checkIn->id,
            'user_id' => $request->user()->id,
        ]);
        if ($verification->wasRecentlyCreated) {
            $this->service->confirm($checkIn);
        }

        return response()->json(['data' => $checkIn->fresh()]);
    }

    public function withdraw(WithdrawInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
        $this->service->withdraw($checkIn);
        return response()->json(['data' => $checkIn->fresh()]);
    }

    public function dispute(DisputeInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
    $this->service->dispute($checkIn, $request->user(), $request->filled('reason') ? (string) $request->input('reason') : null);
        return response()->json(['data' => $checkIn->fresh()]);
    }

    public function resolve(ResolveInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
    $this->service->resolve($checkIn, (string) $request->input('resolution'));
        return response()->json(['data' => $checkIn->fresh()]);
    }

    public function suppress(SuppressInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
    $this->service->suppress($checkIn, $request->user(), (string) $request->input('reason'));
        return response()->json(['data' => $checkIn->fresh()]);
    }

    public function unsuppress(UnsuppressInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
        $this->service->unsuppress($checkIn);
        return response()->json(['data' => $checkIn->fresh()]);
    }

    public function flag(FlagInstitutionCheckInRequest $request, InstitutionCheckIn $checkIn): JsonResponse
    {
        // For now, record via activity log; can be extended later to a dedicated table
        activity()
            ->performedOn($checkIn)
            ->causedBy($request->user())
            ->withProperties(['reason' => $request->filled('reason') ? (string) $request->input('reason') : null])
            ->event('flagged')
            ->log('Check-in flagged');

        return response()->json(['data' => $checkIn->fresh()]);
    }
}
