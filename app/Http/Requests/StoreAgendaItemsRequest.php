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
     * The class-level create check is tenant-agnostic (see HasCommonChecks::create), and the
     * meeting these items are filed under comes from request input — so the meeting itself has
     * to be authorized as an object, exactly as AgendaItemController::reorder() does.
     */
    public function authorize(): bool
    {
        if (! $this->user()->can('create', AgendaItem::class)) {
            return false;
        }

        $meeting = Meeting::query()->find($this->input('meeting_id'));

        // Leave a missing meeting to the exists rule so it reads as a validation error.
        if ($meeting === null) {
            return true;
        }

        return $this->user()->can('update', $meeting);
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
