<?php

namespace App\Http\Requests;

use App\Models\Institution;
use Illuminate\Support\Str;

class StoreInstitutionRequest extends InstitutionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Institution::class);
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'alias' => $this->alias ?? Str::slug($this->name['lt']),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name.lt' => 'required|unique:institutions,name',
            'short_name.lt' => 'nullable|unique:institutions,short_name',
            'alias' => 'nullable|unique:institutions,alias',
        ]);
    }
}
