<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexTaskSummaryRequest extends BaseIndexRequest
{
    /**
     * Normalize `taskable_type` to an array before validation.
     *
     * The filter is a real multiselect (a caller may want institution *and* meeting tasks at
     * once — a periodicity-gap task and an agenda task are both "about a meeting"), but a
     * hand-typed or bookmarked URL carries a single value as a plain string rather than
     * `taskable_type[]=...`. Wrap it so the `array` rule below applies uniformly.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('taskable_type'))) {
            $this->merge(['taskable_type' => [$this->input('taskable_type')]]);
        }
    }

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
            'taskable_type' => ['nullable', 'array'],
            'taskable_type.*' => ['string', Rule::in(Task::TASKABLE_TYPES)],
            'completion' => ['nullable', 'string', Rule::in(['pending', 'completed', 'all'])],
        ]);
    }
}
