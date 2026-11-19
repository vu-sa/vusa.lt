<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
