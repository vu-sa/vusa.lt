<?php

namespace App\Http\Requests;

use App\Models\Task;

class StoreTaskRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', [Task::class, $this->authorizer]);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'taskable_id' => $this->input('taskable_id') ?? auth()->id(),
            'taskable_type' => $this->input('taskable_type') ?? 'App\\Models\\User',
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
            'taskable_type' => 'required',
            'due_date' => 'required|date:Y-m-d',
            'responsible_people' => 'array',
            // if responsible_people provided, separate_tasks must be included
            'separate_tasks' => 'required_if:responsible_people,!=,null',
        ];
    }
}
