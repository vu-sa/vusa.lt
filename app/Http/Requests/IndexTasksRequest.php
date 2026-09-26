<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Filters of the task collection (both scopes, page and API), as `useDatabaseCollectionSource`
 * sends them: multi-value facets as `field[]=…`, single ones as `field=…`.
 */
class IndexTasksRequest extends BaseIndexRequest
{
    #[\Override]
    protected int $defaultPerPage = 50;

    #[\Override]
    protected function prepareForValidation(): void
    {
        // A bookmarked or hand-typed URL carries `?taskable_type=meeting` or a comma list.
        foreach (['taskable_type', 'tenant'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => explode(',', $this->input($key))]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'scope' => ['nullable', 'string', Rule::in(['mine', 'tenant'])],
            'completion' => ['nullable', 'string', Rule::in(['pending', 'completed', 'all'])],
            'taskable_type' => ['nullable', 'array'],
            'taskable_type.*' => ['string', Rule::in(Task::TASKABLE_TYPES)],
            'tenant' => ['nullable', 'array'],
            'tenant.*' => ['integer'],
            'assigned' => ['nullable', 'string', Rule::in(['me'])],
            'overdue' => ['nullable', 'string', Rule::in(['1'])],
            'auto' => ['nullable', 'string', Rule::in(['1'])],
            'item' => ['nullable', 'string'],
        ]);
    }

    /** Pending by default: the listing is an action list. */
    public function completion(): string
    {
        return $this->validated('completion') ?? 'pending';
    }

    public function sortsBy(string $column): bool
    {
        return ($this->getSorting()[0]['id'] ?? null) === $column;
    }
}
