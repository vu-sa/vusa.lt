<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MergeDutiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Merging changes the kept duty (its assignments grow) and destroys each
     * source, so both abilities are required — same shape as MergeUsersRequest.
     */
    public function authorize(): bool
    {
        $keptDuty = Duty::find($this->target_duty_id);

        if (! $keptDuty || ! $this->user()->can('update', $keptDuty)) {
            return false;
        }

        $sourceIds = (array) $this->source_duty_ids;

        foreach (Duty::query()->whereIn('id', $sourceIds)->get() as $sourceDuty) {
            if (! $this->user()->can('delete', $sourceDuty)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_duty_id' => ['required', 'ulid', SoftDeleteRules::existsLive('duties')],
            'source_duty_ids' => ['required', 'array', 'min:1'],
            'source_duty_ids.*' => ['required', 'ulid', SoftDeleteRules::existsLive('duties'), 'different:target_duty_id'],
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
