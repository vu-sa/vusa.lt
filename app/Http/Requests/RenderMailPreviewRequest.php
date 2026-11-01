<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RenderMailPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Role::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'required', 'string', Rule::in(['lt', 'en'])],
            'format' => ['sometimes', 'required', 'string', Rule::in(['html', 'text'])],
        ];
    }
}
