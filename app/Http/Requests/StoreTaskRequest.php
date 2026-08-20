<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * `tasks.create` is tenant-agnostic and the model a task hangs off comes from request
     * input, so the taskable is authorized as an object too. The bar is `view`, not `update`:
     * student representatives hold `tasks.create.padalinys` alongside only
     * `institutions.read.own`, and filing a task on their own institution is exactly what the
     * feature is for. `view` still closes the cross-tenant hole, since neither the `own` nor
     * the `padalinys` read scope matches another tenant's record.
     */
    public function authorize(): bool
    {
        if (! $this->user()->can('create', Task::class)) {
            return false;
        }

        $taskableType = $this->input('taskable_type');

        if (! in_array($taskableType, Task::TASKABLE_TYPES, true)) {
            // Leave the Rule::in below to report it as a validation error.
            return true;
        }

        if ($taskableType === MorphMap::alias(User::class)) {
            return (string) $this->input('taskable_id') === (string) $this->user()->id;
        }

        $taskable = MorphMap::classFor($taskableType)::query()->find($this->input('taskable_id'));

        if ($taskable === null) {
            return true;
        }

        return $this->user()->can('view', $taskable);
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'taskable_id' => $this->input('taskable_id') ?? auth()->id(),
            'taskable_type' => $this->input('taskable_type') ?? User::class,
            'due_date' => date('Y-m-d', $this->input('due_date') / 1000),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'taskable_id' => 'required',
            'taskable_type' => ['required', Rule::in(Task::TASKABLE_TYPES)],
            'due_date' => 'required|date:Y-m-d',
            'responsible_people' => 'array',
            // if responsible_people provided, separate_tasks must be included
            'separate_tasks' => 'required_if:responsible_people,!=,null',
        ];
    }
}
