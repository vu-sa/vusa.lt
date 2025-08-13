<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuppressInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkIn = $this->route('checkIn');
        return $this->user()?->can('suppress', $checkIn) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
