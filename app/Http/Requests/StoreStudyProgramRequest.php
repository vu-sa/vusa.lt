<?php

namespace App\Http\Requests;

use App\Enums\DegreeEnum;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\StudyProgram;
use App\Rules\TranslatableField;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudyProgramRequest extends FormRequest
{
    use ValidatesTenantScope;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array', new TranslatableField(['lt'])],
            'name.lt' => ['required', 'string', 'max:255', Rule::unique('study_programs', 'name->lt')->withoutTrashed()],
            'name.en' => 'nullable|string|max:255',
            'degree' => ['required', 'string', DegreeEnum::getValidationRule()],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('studyPrograms.create.padalinys')],
        ];
    }
}
