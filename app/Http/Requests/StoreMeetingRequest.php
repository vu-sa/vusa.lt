<?php

namespace App\Http\Requests;

use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Rules\WithinAuthorizedTenantScope;
use Illuminate\Foundation\Http\FormRequest;
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
        return $this->user()->can('create', Meeting::class);
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
}
