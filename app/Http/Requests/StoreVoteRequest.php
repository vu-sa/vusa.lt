<?php

namespace App\Http\Requests;

use App\Enums\VoteValue;
use App\Models\Pivots\AgendaItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $agendaItem = AgendaItem::find($this->agenda_item_id);

        return $agendaItem && $this->user()->can('update', $agendaItem);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'agenda_item_id' => 'required|exists:agenda_items,id',
            'is_main' => 'nullable|boolean',
            'title' => 'nullable|string|max:200',
            'student_vote' => ['nullable', new Enum(VoteValue::class)],
            'decision' => ['nullable', new Enum(VoteValue::class)],
            'student_benefit' => ['nullable', new Enum(VoteValue::class)],
            'note' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'agenda_item_id.required' => trans('voting.validation.agenda_item_required'),
            'agenda_item_id.exists' => trans('voting.validation.agenda_item_exists'),
            'title.string' => trans('voting.validation.title_string'),
            'title.max' => trans('voting.validation.title_max'),
            // The rules use `new Enum(VoteValue::class)`, which reports under the `enum` key —
            // an `in` key here would never fire.
            'student_vote.enum' => trans('voting.validation.student_vote_enum'),
            'decision.enum' => trans('voting.validation.decision_enum'),
            'student_benefit.enum' => trans('voting.validation.student_benefit_enum'),
            'note.max' => trans('voting.validation.note_max'),
        ];
    }
}
