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

        // A duty cannot follow itself, nor the duty that followed it.
        if ($predecessor->is($duty) || $duty->successors()->whereKey($predecessor->getKey())->exists()) {
            $fail(__('Ši pareigybė negali būti savo pačios pirmtakė.'));
        }
    }
}
