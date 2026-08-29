<?php

namespace App\Http\Requests\Dutiables;

use App\Actions\Dutiables\MergeDutiables;
use App\Models\Pivots\Dutiable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MergeDutiablesRequest extends FormRequest
{
    /**
     * Every targeted row must pass `manageDutiable`, as with the operation batches: a
     * merge that is only partly permitted is refused outright rather than half-applied.
     */
    public function authorize(): bool
    {
        $rows = $this->rows();

        if ($rows->isEmpty()) {
            return true;
        }

        return $rows->every(fn (Dutiable $row) => $this->user()->can('manageDutiable', $row));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'row_ids' => ['required', 'array', 'min:2', 'max:50'],
            'row_ids.*' => ['ulid', Rule::exists('dutiables', 'id')],
            'acknowledge_access_change' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Checked here rather than in the action so a crafted payload gets a 422 with
            // a reason, not a silent merge of two different people's seats.
            if (! MergeDutiables::isMergeable($this->rows())) {
                $validator->errors()->add('row_ids', __('dutiables.timeline.actions.merge_invalid'));
            }
        });
    }

    /**
     * @return Collection<int, Dutiable>
     */
    public function rows(): Collection
    {
        $ids = array_values(array_filter((array) $this->input('row_ids', []), 'is_string'));

        if ($ids === []) {
            return new Collection;
        }

        return Dutiable::query()
            ->without('study_program')
            ->with('duty.assignableTenants', 'user')
            ->whereIn('id', $ids)
            ->get();
    }
}
