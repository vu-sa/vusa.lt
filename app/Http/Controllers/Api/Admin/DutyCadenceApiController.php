<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Http\Controllers\Api\ApiController;
use App\Models\Cadence;
use App\Models\Duty;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/** The terms offered as date presets when assigning someone to a duty, current or past. */
class DutyCadenceApiController extends ApiController
{
    public function index(Duty $duty): JsonResponse
    {
        $this->authorizeApi('managePeople', $duty);

        $today = Carbon::today();

        return $this->jsonSuccess(
            ResolveCadenceForDuty::applicableFor($duty)
                ->sortByDesc(fn (Cadence $cadence) => $cadence->start_date)
                ->map(fn (Cadence $cadence) => [
                    'id' => $cadence->id,
                    'label' => $cadence->label,
                    'start_date' => $cadence->start_date->toDateString(),
                    'end_date' => $cadence->end_date->toDateString(),
                    'is_current' => $cadence->contains($today),
                ])
                ->values()
        );
    }
}
