<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexTaskSummaryRequest extends BaseIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Adds the summary-specific filters on top of the shared paging/sorting rules. Before this
     * existed the method took a plain Request, so `per_page` was read straight from input and
     * the `max:100` cap on BaseIndexRequest never applied.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'tenant_ids' => 'nullable|array',
            'tenant_ids.*' => 'integer|exists:tenants,id',
            // 'institutions' is a UI group covering both Institution and Meeting taskables.
            'taskable_type' => ['nullable', 'string', Rule::in([...Task::TASKABLE_TYPES, 'institutions'])],
            'completion' => ['nullable', 'string', Rule::in(['pending', 'completed'])],
        ]);
    }
}
