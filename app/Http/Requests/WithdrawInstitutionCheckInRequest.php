<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkIn = $this->route('checkIn');
        return $this->user()?->can('withdraw', $checkIn) ?? false;
    }

    public function rules(): array
    {
        return [
            // no body fields
        ];
    }
}
