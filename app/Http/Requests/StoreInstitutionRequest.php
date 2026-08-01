<?php

namespace App\Http\Requests;

use App\Models\Institution;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreInstitutionRequest extends InstitutionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Institution::class);
    }

    #[\Override]
    public function prepareForValidation(): void
    {
        $this->merge([
            'alias' => $this->alias ?? Str::slug($this->name['lt']),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // No database unique index backs this, so a soft-deleted record must not
            // reserve the value — `withoutTrashed()` is exactly right here.
            'name.lt' => ['required', Rule::unique('institutions', 'name')->withoutTrashed()],
            'short_name.lt' => ['nullable', Rule::unique('institutions', 'short_name')->withoutTrashed()],
            'alias' => ['nullable', Rule::unique('institutions', 'alias')->withoutTrashed()],
        ]);
    }
}
