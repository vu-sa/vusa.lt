<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $institution = $this->route('institution');

        return $this->user()?->can('create', [InstitutionCheckIn::class, $institution]) ?? false;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date', Rule::date()->beforeOrEqual(today()->addMonths(3))],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** A period can start in the past now, so it must not deny a meeting that is already recorded. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $institution = $this->route('institution');
            if ($validator->errors()->isNotEmpty() || ! $institution instanceof Institution) {
                return;
            }
            $meeting = $institution->meetings()
                ->whereDate('start_time', '>=', $this->date('start_date'))
                ->whereDate('start_time', '<=', $this->date('end_date'))
                ->orderBy('start_time')
                ->first();
            if ($meeting !== null) {
                $validator->errors()->add('start_date', __('action_window.check_in.meeting_in_range', ['date' => $meeting->start_time->toDateString()]));
            }
        }];
    }
}
