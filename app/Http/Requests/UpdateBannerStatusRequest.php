<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('banner'));
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
