<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MergeDutiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('merge', Duty::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_duty_id' => ['required', 'ulid', SoftDeleteRules::existsLive('duties')],
            'source_duty_ids' => ['required', 'array', 'min:1'],
            'source_duty_ids.*' => ['required', 'ulid', 'distinct', SoftDeleteRules::existsLive('duties'), 'different:target_duty_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'source_duty_ids.*.different' => trans('forms.validation.merge.source_is_target'),
        ];
    }
}
