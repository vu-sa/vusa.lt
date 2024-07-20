<?php

namespace App\Http\Requests;

use App\Models\Institution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreInstitutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Institution::class, $this->authorizer]);
    }

    public function prepareForValidation(): void
    {
        // if request alias is null, create slug from name
        $this->merge([
            'alias' => $this->alias ?? Str::slug($this->name),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|unique:institutions,name',
            'short_name' => 'required|unique:institutions,short_name',
            'alias' => 'nullable|unique:institutions,alias',
            'tenant_id' => 'required',
            'image_url' => 'nullable|url',
            'extra_attributes' => 'nullable|array',
            'types' => 'nullable|array',
        ];
    }
}
