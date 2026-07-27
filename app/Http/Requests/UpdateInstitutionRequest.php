<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateInstitutionRequest extends InstitutionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->institution);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // No database unique index backs this, so a soft-deleted record must not
            // reserve the value — `withoutTrashed()` is exactly right here.
            'name.lt' => ['required', Rule::unique('institutions', 'name')->ignore($this->institution->id)->withoutTrashed()],
            'short_name.lt' => ['nullable', Rule::unique('institutions', 'short_name')->ignore($this->institution->id)->withoutTrashed()],
            'alias' => ['nullable', Rule::unique('institutions', 'alias')->ignore($this->institution->id)->withoutTrashed()],
        ]);
    }
}
