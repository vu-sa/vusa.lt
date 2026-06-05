<?php

namespace App\Http\Requests;

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
            'name.lt' => 'required|unique:institutions,name,'.$this->institution->id,
            'short_name.lt' => 'nullable|unique:institutions,short_name,'.$this->institution->id,
            'alias' => 'nullable|unique:institutions,alias,'.$this->institution->id,
        ]);
    }
}
