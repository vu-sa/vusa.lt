<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkIn = $this->route('checkIn');
        return $this->user()?->can('resolve', $checkIn) ?? false;
    }

    public function rules(): array
    {
        return [
            'resolution' => ['required', 'in:keep,withdraw'],
        ];
    }
}
