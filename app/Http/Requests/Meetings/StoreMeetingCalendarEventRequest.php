<?php

namespace App\Http\Requests\Meetings;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Calendar;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Announce a meeting in the calendar, either by creating a fresh event or adopting one that
 * already exists.
 */
class StoreMeetingCalendarEventRequest extends FormRequest
{
    use ValidatesTenantScope;

    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('update', $this->meeting())) {
            return false;
        }

        // Adopting an existing event edits it; making a new one creates in the meeting's tenant.
        return $this->filled('calendar_id')
            ? $user->can('update', $this->existingEvent())
            : $user->can('create', Calendar::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'calendar_id' => [
                'nullable',
                'integer',
                Rule::exists('calendar', 'id')->whereNull('deleted_at'),
                // Resolving an id straight from the payload would otherwise reach any event in
                // the database, including other tenants' and ones already spoken for.
                Rule::in($this->adoptableEventIds()),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        // A meeting can only stand behind one announcement.
        abort_if(
            $this->meeting()->calendarEvent()->exists(),
            409,
            'This meeting already has a calendar event.'
        );
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }

    private function existingEvent(): Calendar
    {
        return Calendar::query()->findOrNew($this->input('calendar_id'));
    }

    /**
     * Unlinked events in a tenant the user may write calendar entries for.
     *
     * @return array<int, int>
     */
    private function adoptableEventIds(): array
    {
        return Calendar::query()
            ->whereNull('meeting_id')
            ->whereIn('tenant_id', $this->authorizedTenantIds('calendars.update.padalinys'))
            ->pluck('id')
            ->map(intval(...))
            ->all();
    }
}
