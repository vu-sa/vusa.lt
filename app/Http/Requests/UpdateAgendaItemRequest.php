<?php

namespace App\Http\Requests;

use App\Enums\AgendaItemType;
use App\Enums\VoteValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAgendaItemRequest extends FormRequest
{
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
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'order' => 'sometimes|integer|min:1',
            'brought_by_students' => 'nullable|boolean',
            'type' => ['nullable', new Enum(AgendaItemType::class)],
            'student_position' => 'nullable|string|max:5000',
            // Votes validation
            'votes' => 'nullable|array',
            'votes.*.id' => 'nullable|string',
            'votes.*.is_main' => 'nullable|boolean',
            'votes.*.is_consensus' => 'nullable|boolean',
            'votes.*.title' => 'nullable|string|max:200',
            'votes.*.student_vote' => ['nullable', new Enum(VoteValue::class)],
            'votes.*.decision' => ['nullable', new Enum(VoteValue::class)],
            'votes.*.student_benefit' => ['nullable', new Enum(VoteValue::class)],
            'votes.*.note' => 'nullable|string|max:2000',
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
            'title.string' => trans('forms.validation.agenda_item.title_string'),
            'title.max' => trans('forms.validation.agenda_item.title_max'),
            'description.string' => trans('forms.validation.agenda_item.description_string'),
            'order.integer' => trans('forms.validation.agenda_item.order_integer'),
            'order.min' => trans('forms.validation.agenda_item.order_min'),
            'type.in' => trans('forms.validation.agenda_item.type_in'),
            'student_position.max' => trans('forms.validation.agenda_item.student_position_max'),
        ];
    }
}
