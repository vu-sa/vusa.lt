<?php

namespace App\Http\Requests;

use App\Models\PlanningProcess;
use App\Models\Problem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanningProcessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var PlanningProcess $planningProcess */
        $planningProcess = $this->route('planningProcess');

        return $this->user()->can('update', $planningProcess);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expectations_text' => ['nullable', 'string'],
            'expectations_submitted_at' => ['nullable', 'date'],
            'meeting_1_notes' => ['nullable', 'string'],
            'meeting_1_date' => ['nullable', 'date'],
            'meeting_2_notes' => ['nullable', 'string'],
            'meeting_2_date' => ['nullable', 'date'],
            'selected_problem_id' => ['nullable', 'string', 'exists:problems,id', function ($attribute, $value, $fail) {
                if ($value) {
                    /** @var PlanningProcess $planningProcess */
                    $planningProcess = $this->route('planningProcess');
                    $problem = Problem::find($value);

                    if ($problem && $problem->tenant_id !== $planningProcess->tenant_id) {
                        $fail(__('validation.same_tenant'));
                    }
                }
            }],
            'goal_text' => ['nullable', 'string'],
            'goal_approved_at' => ['nullable', 'date'],
            'reflection_text' => ['nullable', 'string'],
            'reflection_submitted_at' => ['nullable', 'date'],
        ];
    }
}
