<?php

namespace App\Http\Requests\Cadences;

use App\Models\Cadence;
use App\Policies\CadencePolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Shared rules for the cadence CRUD pair.
 *
 * Authorization delegates to {@see CadencePolicy}: the global ladder is
 * settings config, an institution override belongs to whoever may edit that institution.
 */
abstract class CadenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cadence = $this->route('cadence');

        if ($cadence instanceof Cadence) {
            return $this->user()->can('update', $cadence)
                // Moving an override between institutions needs rights on both sides.
                && $this->user()->can('createFor', [Cadence::class, $this->institutionId()]);
        }

        return $this->user()->can('createFor', [Cadence::class, $this->institutionId()]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => ['nullable', 'ulid', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'start_date' => ['required', 'date', $this->uniqueStartRule()],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * MySQL treats NULLs as distinct, so the `(institution_id, start_date)` unique index
     * does not stop a second global row from claiming a start date. Constrain it here.
     */
    protected function uniqueStartRule(): Unique
    {
        $institutionId = $this->institutionId();

        $rule = Rule::unique('cadences', 'start_date');

        $rule = $institutionId === null
            ? $rule->whereNull('institution_id')
            : $rule->where('institution_id', $institutionId);

        return $this->ignoredCadenceId() !== null
            ? $rule->ignore($this->ignoredCadenceId())
            : $rule;
    }

    protected function institutionId(): ?string
    {
        $value = $this->input('institution_id');

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function ignoredCadenceId(): ?string
    {
        return null;
    }
}
