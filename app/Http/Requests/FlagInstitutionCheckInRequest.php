<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlagInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkIn = $this->route('checkIn');
        return $this->user()?->can('flag', $checkIn) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
