<?php

namespace App\Http\Requests;

use App\Enums\DegreeEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudyProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('studyProgram'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studyProgramId = $this->route('studyProgram')->id;

        return [
            'name.lt' => 'required|string|max:255|unique:study_programs,name->lt,'.$studyProgramId,
            'name.en' => 'nullable|string|max:255',
            'degree' => ['required', 'string', DegreeEnum::getValidationRule()],
            'tenant_id' => 'required|exists:tenants,id',
        ];
    }
}
