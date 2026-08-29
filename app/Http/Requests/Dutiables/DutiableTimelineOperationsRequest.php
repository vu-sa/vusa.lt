<?php

namespace App\Http\Requests\Dutiables;

use App\Models\Pivots\Dutiable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Shared shape for the two consumers of the timeline planner: the dry-run preview and
 * the write. Keeping the rules in one place is what stops a preview from accepting an
 * operation the apply endpoint would reject.
 */
abstract class DutiableTimelineOperationsRequest extends FormRequest
{
    public const array TYPES = [
        'set_dates',
        'align_to_cadence',
        'close_open_ended',
    ];

    /**
     * Editing any row requires `manageDutiable` on **every** targeted row: a batch that
     * is only partly permitted is refused outright rather than half-applied.
     *
     * Rows that do not resolve return true so the `exists` rule reports 422 instead of
     * the whole request 403-ing on a typo.
     */
    public function authorize(): bool
    {
        $rows = $this->targetedRows();

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
            'operations' => ['required', 'array', 'min:1', 'max:50'],
            'operations.*.type' => ['required', Rule::in(self::TYPES)],
            'operations.*.row_ids' => ['required', 'array', 'min:1', 'max:1500'],
            'operations.*.row_ids.*' => ['ulid', Rule::exists('dutiables', 'id')],
            'operations.*.start_date' => ['nullable', 'date_format:Y-m-d'],
            'operations.*.end_date' => ['nullable', 'date_format:Y-m-d'],
            'operations.*.cadence_id' => ['nullable', 'ulid', Rule::exists('cadences', 'id')],
            'operations.*.edges' => ['nullable', Rule::in(['start', 'end', 'both'])],
            'operations.*.threshold_days' => ['nullable', 'integer', 'between:0,3650'],
        ];
    }

    /**
     * Each operation type needs its own parameter; without this a `close_open_ended` with
     * no `end_date` would validate and then silently do nothing.
     *
     * `align_to_cadence` deliberately has none: omitting `cadence_id` means "each edge to
     * its own nearest term", which is how a row spanning two cadences is straightened.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ((array) $this->input('operations', []) as $index => $operation) {
                $required = match ($operation['type'] ?? null) {
                    'close_open_ended' => 'end_date',
                    default => null,
                };

                if ($required !== null && ($operation[$required] ?? null) === null) {
                    $validator->errors()->add(
                        "operations.{$index}.{$required}",
                        __('validation.required', ['attribute' => $required]),
                    );
                }

                if (($operation['type'] ?? null) === 'set_dates'
                    && ($operation['start_date'] ?? null) === null
                    && ! array_key_exists('end_date', $operation)) {
                    $validator->errors()->add(
                        "operations.{$index}.start_date",
                        __('validation.required', ['attribute' => 'start_date']),
                    );
                }
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function operations(): array
    {
        /** @var list<array<string, mixed>> $operations */
        $operations = $this->validated('operations');

        return $operations;
    }

    /**
     * @return Collection<int, Dutiable>
     */
    protected function targetedRows(): Collection
    {
        $ids = [];

        foreach ((array) $this->input('operations', []) as $operation) {
            foreach ((array) ($operation['row_ids'] ?? []) as $rowId) {
                if (is_string($rowId)) {
                    $ids[$rowId] = true;
                }
            }
        }

        if ($ids === []) {
            return collect();
        }

        return Dutiable::query()
            ->without('study_program')
            ->with('duty.assignableTenants', 'user')
            ->whereIn('id', array_keys($ids))
            ->get();
    }
}
