<?php

namespace App\Http\Requests\Meetings;

use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Detach a meeting from its announcement. The event itself is left alone.
 */
class DestroyMeetingCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $event = $this->meeting()->calendarEvent;

        return $user !== null
            && $user->can('update', $this->meeting())
            && ($event === null || $user->can('update', $event));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
