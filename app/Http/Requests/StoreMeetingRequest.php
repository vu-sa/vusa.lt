<?php

namespace App\Http\Requests;

use App\Enums\MeetingType;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Rules\WithinAuthorizedTenantScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (! $this->user()->can('create', Meeting::class)) {
            return false;
        }

        // Linking an existing announcement rewrites that event, so it needs the event's
        // own permission too. A missing id is left to the `exists` rule to report.
        $calendar = $this->input('calendar_id') !== null
            ? Calendar::query()->find($this->input('calendar_id'))
            : null;

        return $calendar === null || $this->user()->can('update', $calendar);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'start_time' => 'required|date',
            'institution_id' => [
                'required',
                'ulid',
                'exists:institutions,id',
                // Meeting::create() is gated by the tenant-agnostic meetings.create check, so
                // the institution the meeting is filed under has to be scoped here.
                new WithinAuthorizedTenantScope(Institution::class, 'meetings.create.padalinys'),
            ],
            'type' => ['nullable', new Enum(MeetingType::class)],
            'description' => 'nullable|string|max:1000',
            'announce_in_calendar' => 'nullable|boolean',
            // An existing announcement this meeting is being created from. Only an
            // unlinked event qualifies: a calendar event stands for at most one meeting.
            'calendar_id' => [
                'nullable',
                'integer',
                Rule::exists('calendar', 'id')->whereNull('meeting_id')->whereNull('deleted_at'),
            ],
            // Not stored: it only tells the redirect to land on the bulk agenda dialog.
            'open_bulk_agenda' => 'nullable|boolean',
            'agendaItems' => 'nullable|array',
            'agendaItems.*.title' => 'required|string|max:255',
            'agendaItems.*.description' => 'nullable|string|max:1000',
            'agendaItems.*.order' => 'required|integer|min:1',
            'agendaItems.*.brought_by_students' => 'nullable|boolean',
            // Batch creation, unlike UpdateAgendaItemRequest's single-item form, has no
            // straightforward way to conditionally require `after:` only for rows that set a
            // start time — leave ordering a client-side concern here.
            'agendaItems.*.start_time' => 'nullable|date_format:H:i',
            'agendaItems.*.end_time' => 'nullable|date_format:H:i',
        ];
    }

    /**
     * Only VU SA's own bodies are announced in the public calendar, so the checkbox the
     * client hides for an external body is refused here too.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('announce_in_calendar')) {
                    return;
                }

                $institution = Institution::query()->find($this->input('institution_id'));

                // A missing institution is already reported by the `exists` rule above;
                // adding a second error for it would only be noise.
                if ($institution === null || $institution->governance_scope->isInternal()) {
                    return;
                }

                $validator->errors()->add('announce_in_calendar', __('meetings.announce.external_not_allowed'));
            },
        ];
    }
}
