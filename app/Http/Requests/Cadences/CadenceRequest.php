<?php

namespace App\Http\Requests\Cadences;

use App\Models\Cadence;
use App\Models\Meeting;
use App\Policies\CadencePolicy;
use App\Policies\MeetingPolicy;
use Closure;
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
    /**
     * Resolved once per boundary: prepareForValidation and the rules ask for the same meeting.
     *
     * @var array<string, Meeting|null>
     */
    private array $anchorMeetings = [];

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
     * An anchored boundary is derived, so the date is filled in here rather than trusted from
     * the client: `after:start_date` and the unique-start rule both need the resolved value,
     * and a browser that posts a stale date must not be able to move the term with it.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $derived = [];

        foreach (['start' => 'start_date', 'end' => 'end_date'] as $side => $dateKey) {
            $meeting = $this->anchorMeeting($side.'_meeting_id');

            if ($meeting !== null) {
                $derived[$dateKey] = $meeting->start_time->toDateString();
            }
        }

        if ($derived !== []) {
            $this->merge($derived);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => ['nullable', 'ulid', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'start_meeting_id' => ['nullable', 'ulid', $this->anchorRule('start_meeting_id')],
            'end_meeting_id' => ['nullable', 'ulid', $this->anchorRule('end_meeting_id')],
            'start_date' => ['required', 'date', $this->uniqueStartRule()],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * A term routinely opens at another body's sitting — a faculty term at the tenant
     * conference — so any meeting is anchorable, but only one the editor may already see:
     * an anchor names the meeting back on the form and follows its date from then on.
     *
     * The global ladder belongs to no institution and therefore anchors to nothing.
     */
    private function anchorRule(string $key): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($key): void {
            if ($value === null || $value === '') {
                return;
            }

            if ($this->anchorMeeting($key) === null) {
                $fail(__('cadences.validation.anchor_not_allowed'));
            }
        };
    }

    /**
     * Resolved through {@see MeetingPolicy::view}, never straight from the id:
     * prepareForValidation runs before any rule, so this is the one place an unvetted id
     * could reach a date.
     */
    private function anchorMeeting(string $key): ?Meeting
    {
        if (! array_key_exists($key, $this->anchorMeetings)) {
            $this->anchorMeetings[$key] = $this->resolveAnchorMeeting($key);
        }

        return $this->anchorMeetings[$key];
    }

    private function resolveAnchorMeeting(string $key): ?Meeting
    {
        $id = $this->input($key);

        if ($this->institutionId() === null || ! is_string($id) || $id === '') {
            return null;
        }

        $meeting = Meeting::query()->with('institutions')->find($id);

        return $meeting !== null && $this->user()->can('view', $meeting) ? $meeting : null;
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
