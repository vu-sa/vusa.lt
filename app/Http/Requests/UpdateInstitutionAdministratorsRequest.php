<?php

namespace App\Http\Requests;

use App\Models\Cadence;
use App\Models\Institution;
use App\Policies\CadencePolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Staffing a term is the same right as defining one, so this uses the gate
 * {@see CadencePolicy} applies to an institution override.
 */
class UpdateInstitutionAdministratorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->institution());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cadence_id' => ['required', 'ulid', $this->applicableCadenceRule()],
            'user_ids' => ['present', 'array'],
            // Deliberately any user: a tenant coordinator with no duty in the body is a
            // legitimate administrator. The picker only *suggests* current members.
            'user_ids.*' => ['ulid', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }

    public function institution(): Institution
    {
        /** @var Institution $institution */
        $institution = $this->route('institution');

        return $institution;
    }

    /**
     * The cadence the roster is being attached to, once validation has passed.
     */
    public function cadence(): Cadence
    {
        /** @var Cadence $cadence */
        $cadence = Cadence::query()->findOrFail($this->validated('cadence_id'));

        return $cadence;
    }

    /**
     * A bare `exists:cadences,id` would be an IDOR: any term id would attach a roster,
     * including another body's. The id has to resolve through this institution's own
     * ladder — its overrides when it has any, otherwise the global rows it inherits.
     * Same own-wins-outright rule as ResolveCadenceForInstitution.
     */
    private function applicableCadenceRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $institution = $this->institution();

            $applicable = $institution->cadences()->exists()
                ? Cadence::query()->forInstitution($institution->id)
                : Cadence::query()->globalLadder();

            if (! $applicable->whereKey($value)->exists()) {
                $fail(__('validation.exists', ['attribute' => $attribute]));
            }
        };
    }
}
