<?php

namespace App\Http\Requests;

use App\Enums\DegreeEnum;
use App\Rules\TranslatableField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studyProgramId = $this->route('studyProgram')->id;

        return [
            'name' => ['required', 'array', new TranslatableField(['lt'])],
            'name.lt' => ['required', 'string', 'max:255', Rule::unique('study_programs', 'name->lt')->ignore($studyProgramId)->withoutTrashed()],
            'name.en' => 'nullable|string|max:255',
            'degree' => ['required', 'string', DegreeEnum::getValidationRule()],
            'tenant_id' => 'required|exists:tenants,id',
        ];
    }
}
