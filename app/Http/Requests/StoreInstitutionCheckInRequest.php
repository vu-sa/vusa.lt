<?php

namespace App\Http\Requests;

use App\Models\InstitutionCheckIn;
use Illuminate\Foundation\Http\FormRequest;
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
            'mode' => ['required', 'in:blackout,heads_up'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:60'],
            'confidence' => ['required', 'in:low,medium,high'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $mode = $this->input('mode', 'blackout');
            $duration = (int) $this->input('duration_days');

            if ($mode === 'blackout' && !in_array($duration, [7, 14, 28, 60], true)) {
                $v->errors()->add('duration_days', __('validation.in'));
            }

            if ($mode === 'heads_up' && ($duration < 1 || $duration > 7)) {
                $v->errors()->add('duration_days', __('validation.between.numeric', ['min' => 1, 'max' => 7]));
            }

            // Prevent duplicate active check-ins by the same user for the same institution
            $institution = $this->route('institution');
            $user = $this->user();
            if ($institution && $user) {
                $exists = \App\Models\InstitutionCheckIn::query()
                    ->where('institution_id', $institution->id)
                    ->where('user_id', $user->id)
                    ->whereIn('state', [
                        \App\States\InstitutionCheckIns\Active::class,
                        \App\States\InstitutionCheckIns\Disputed::class,
                        \App\States\InstitutionCheckIns\AdminSuppressed::class,
                    ])->exists();

                if ($exists) {
                    $v->errors()->add('institution_id', __('validation.unique'));
                }
            }
        });
    }
}
