<?php

namespace App\Http\Requests;

use App\Models\Duty;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Linking a predecessor shows its holders on this duty's history, so the linked duty must be one
 * the actor may already see — resolved through DutyPolicy::view, never a bare `exists`.
 */
class StoreDutyPredecessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');

        return $this->user()->can('update', $duty);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'predecessor_id' => ['required', 'ulid', $this->viewablePredecessor(...)],
            'effective_on' => ['required', 'date'],
        ];
    }

    public function predecessor(): Duty
    {
        return Duty::query()->findOrFail($this->validated('predecessor_id'));
    }

    private function viewablePredecessor(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');
        $predecessor = Duty::query()->find($value);

        if ($predecessor === null || ! $this->user()->can('view', $predecessor)) {
            $fail(__('validation.exists', ['attribute' => __('Pareigybė')]));

            return;
        }

        $day = $this->date('effective_on')?->toDateString();

        // A link back to an earlier successor is fine on a later day (merged, then split back), never the same one.
        if ($predecessor->is($duty) || ($day !== null && $duty->successors()->whereKey($predecessor->getKey())->wherePivot('effective_on', $day)->exists())) {
            $fail(__('Ši pareigybė negali būti savo pačios pirmtakė.'));

            return;
        }

        if ($day !== null && $duty->predecessors()->whereKey($predecessor->getKey())->wherePivot('effective_on', $day)->exists()) {
            $fail(__('Ši pareigybė jau susieta šia data.'));
        }
    }
}
