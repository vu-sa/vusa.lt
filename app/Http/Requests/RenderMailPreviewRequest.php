<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RenderMailPreviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'required', 'string', Rule::in(['lt', 'en'])],
            'format' => ['sometimes', 'required', 'string', Rule::in(['html', 'text'])],
        ];
    }
}
