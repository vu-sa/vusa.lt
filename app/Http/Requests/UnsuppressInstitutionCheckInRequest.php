<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnsuppressInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkIn = $this->route('checkIn');
        return $this->user()?->can('unsuppress', $checkIn) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
