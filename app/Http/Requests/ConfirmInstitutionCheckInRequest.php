<?php

namespace App\Http\Requests;

use App\Models\InstitutionCheckIn;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmInstitutionCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkIn = $this->route('checkIn');
        return $this->user()?->can('confirm', $checkIn) ?? false;
    }

    public function rules(): array
    {
        return [
            // no body fields required; idempotent confirm happens via route-model binding
        ];
    }
}
