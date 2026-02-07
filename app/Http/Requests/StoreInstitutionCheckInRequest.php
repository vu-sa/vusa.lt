<?php

namespace App\Http\Requests;

use App\Models\InstitutionCheckIn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}
