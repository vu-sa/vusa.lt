<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DutiableTimelineRequest extends FormRequest
{
    /**
     * Reading the timeline needs `view`, not an edit ability — a coordinator who may
     * only look at a duty still gets the chart; per-row editability is resolved
     * separately in BuildDutiableTimeline.
     */
    public function authorize(): bool
    {
        $model = $this->scopeModel();

        // A scope that does not resolve returns true so the `exists` rules report 422
        // rather than the request 403-ing on a typo.
        return $model === null || $this->user()->can('view', $model);
    }

    /**
     * A query string carries booleans as the strings "true"/"false", which the `boolean`
     * rule rejects outright.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('include_ended')) {
            $this->merge(['include_ended' => $this->boolean('include_ended')]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['user', 'duty', 'institution'])],
            'scope_id' => ['required', 'string', 'max:26'],
            'duty_ids' => ['nullable', 'array', 'max:200'],
            'duty_ids.*' => ['ulid', Rule::exists('duties', 'id')->whereNull('deleted_at')],
            'include_ended' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->scopeModel() === null) {
                $validator->errors()->add('scope_id', __('validation.exists', ['attribute' => 'scope_id']));
            }
        });
    }

    public function scopeModel(): Duty|Institution|User|null
    {
        $scope = $this->input('scope');
        $id = $this->input('scope_id');

        if (! is_string($scope) || ! is_string($id) || $id === '') {
            return null;
        }

        return match ($scope) {
            'user' => User::find($id),
            'duty' => Duty::find($id),
            'institution' => Institution::find($id),
            default => null,
        };
    }
}
