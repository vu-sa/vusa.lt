<?php

namespace App\Http\Requests;

use App\Enums\AgendaItemType;
use App\Enums\VoteValue;
use App\Http\Requests\Concerns\NormalizesTranslatableInput;
use App\Rules\TranslatableField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAgendaItemRequest extends FormRequest
{
    use NormalizesTranslatableInput;

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeTranslatable(
            'title',
            'description',
            'student_position',
            'votes.*.title',
            'votes.*.note',
        );
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', $this->agendaItem);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // Lithuanian is the source language; English stays optional.
            'title' => ['sometimes', 'array', new TranslatableField(['lt'])],
            'title.lt' => 'required_with:title|string',
            'title.en' => 'nullable|string',
            'description' => 'nullable|array',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'order' => 'sometimes|integer|min:1',
            'brought_by_students' => 'nullable|boolean',
            'type' => ['nullable', new Enum(AgendaItemType::class)],
            'student_position' => 'nullable|array',
            'student_position.lt' => 'nullable|string|max:5000',
            'student_position.en' => 'nullable|string|max:5000',
            // The timetable slot for this item. `start_time` has existed since the table was
            // created but nothing ever wrote to it; `end_time` was added alongside the editor.
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => [
                'nullable',
                'date_format:H:i',
                // Only meaningful once a start is set, and `after:` on an unset field always fails.
                ...($this->filled('start_time') ? ['after:start_time'] : []),
            ],
            // Votes validation
            'votes' => 'nullable|array',
            'votes.*.id' => 'nullable|string',
            'votes.*.is_main' => 'nullable|boolean',
            'votes.*.is_consensus' => 'nullable|boolean',
            'votes.*.title' => 'nullable|array',
            'votes.*.title.lt' => 'nullable|string|max:200',
            'votes.*.title.en' => 'nullable|string|max:200',
            'votes.*.student_vote' => ['nullable', new Enum(VoteValue::class)],
            'votes.*.decision' => ['nullable', new Enum(VoteValue::class)],
            'votes.*.student_benefit' => ['nullable', new Enum(VoteValue::class)],
            'votes.*.note' => 'nullable|array',
            'votes.*.note.lt' => 'nullable|string|max:2000',
            'votes.*.note.en' => 'nullable|string|max:2000',
            'votes.*.order' => 'nullable|integer|min:0',
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
            'title.lt.string' => trans('forms.validation.agenda_item.title_string'),
            'title.en.string' => trans('forms.validation.agenda_item.title_string'),
            'title.lt.max' => trans('forms.validation.agenda_item.title_max'),
            'title.en.max' => trans('forms.validation.agenda_item.title_max'),
            'description.lt.string' => trans('forms.validation.agenda_item.description_string'),
            'description.en.string' => trans('forms.validation.agenda_item.description_string'),
            'order.integer' => trans('forms.validation.agenda_item.order_integer'),
            'order.min' => trans('forms.validation.agenda_item.order_min'),
            'type.in' => trans('forms.validation.agenda_item.type_in'),
            'student_position.lt.max' => trans('forms.validation.agenda_item.student_position_max'),
            'student_position.en.max' => trans('forms.validation.agenda_item.student_position_max'),
            'end_time.after' => trans('forms.validation.agenda_item.end_time_after'),
        ];
    }
}
