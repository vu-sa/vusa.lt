<?php

namespace App\Http\Requests\Api\Admin;

use App\Support\Auditables;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Coarse "must be authenticated" gate only, matching
        // AtstovavimasTenantRequest -- the controller authorizes against the
        // resolved subject model itself (the "see it -> audit it" rule, same
        // as the discussion API), which needs the model to be resolved first.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'scope' => ['nullable', Rule::in(['tree', 'self'])],
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'restored', 'relation_updated', 'content_reordered'])],
            'subject_type' => ['nullable', Rule::in(array_keys(Auditables::SUBJECT_TYPES))],
            'causer_id' => ['nullable', 'string', 'max:26'],
        ];
    }
}
