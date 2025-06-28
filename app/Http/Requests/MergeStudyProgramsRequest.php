<?php

namespace App\Http\Requests;

use App\Models\StudyProgram;
use Illuminate\Foundation\Http\FormRequest;

class MergeStudyProgramsRequest extends FormRequest
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
            'target_study_program_id' => 'required|exists:study_programs,id',
            'source_study_program_ids' => 'required|array|min:1',
            'source_study_program_ids.*' => 'required|exists:study_programs,id|different:target_study_program_id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_study_program_ids.*.different' => 'Source study programs cannot include the target study program.',
        ];
    }
}
