<?php

namespace App\Http\Requests;

use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgendaItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The meeting comes from request input, so it is authorized as an object, as
     * AgendaItemController::reorder() does.
     */
    public function authorize(): bool
    {
        $meeting = Meeting::query()->find($this->input('meeting_id'));

        // Leave a missing meeting to the exists rule so it reads as a validation error.
        if ($meeting === null) {
            return $this->user()->can('create', AgendaItem::class);
        }

        return $this->user()->can('createFor', [AgendaItem::class, $meeting]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'agendaItemTitles' => 'required|array',
            'agendaItemTitles.*' => 'required|string',
            'meeting_id' => ['required', 'ulid', SoftDeleteRules::existsLive('meetings')],
            'broughtByStudentsFlags' => 'nullable|array',
            'broughtByStudentsFlags.*' => 'nullable|boolean',
            'privateFlags' => 'nullable|array',
            'privateFlags.*' => 'boolean',
            // Times recognised in a pasted timetable, parallel to the titles.
            'startTimes' => 'nullable|array',
            'startTimes.*' => 'nullable|date_format:H:i',
            'endTimes' => 'nullable|array',
            'endTimes.*' => 'nullable|date_format:H:i',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages()
    {
        return [
            'agendaItemTitles.required' => trans('forms.validation.agenda_item.titles_required'),
            'agendaItemTitles.*.required' => trans('forms.validation.agenda_item.title_required'),
        ];
    }
}
