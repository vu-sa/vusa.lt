<?php

namespace App\Http\Requests;

use App\Enums\DegreeEnum;
use App\Models\StudyProgram;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudyProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', StudyProgram::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required|string|max:255|unique:study_programs,name->lt',
            'name.en' => 'nullable|string|max:255',
            'degree' => ['required', 'string', DegreeEnum::getValidationRule()],
            'tenant_id' => 'required|exists:tenants,id',
        ];
    }
}
