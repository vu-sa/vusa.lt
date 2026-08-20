<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The text-box submission endpoints are all scoped to one content part. Authorization runs in
 * the controller against the *page* that owns it, which needs the validated id first.
 */
class ContentPartScopedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content_part_id' => ['required', 'integer', 'exists:content_parts,id'],
        ];
    }
}
