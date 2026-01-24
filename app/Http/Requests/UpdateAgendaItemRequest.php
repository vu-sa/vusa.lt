<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'type' => 'nullable|string|in:voting,informational,deferred',
            'student_position' => 'nullable|string|max:5000',
            // Votes validation
            'votes' => 'nullable|array',
            'votes.*.id' => 'nullable|string',
            'votes.*.is_main' => 'nullable|boolean',
            'votes.*.title' => 'nullable|string|max:500',
            'votes.*.student_vote' => 'nullable|string|in:positive,negative,neutral',
            'votes.*.decision' => 'nullable|string|in:positive,negative,neutral',
            'votes.*.student_benefit' => 'nullable|string|in:positive,negative,neutral',
            'votes.*.note' => 'nullable|string|max:2000',
            'votes.*.order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'title.string' => 'Darbotvarkės klausimo pavadinimas turi būti tekstas.',
            'title.max' => 'Darbotvarkės klausimo pavadinimas negali būti ilgesnis nei 255 simbolių.',
            'description.string' => 'Darbotvarkės klausimo aprašymas turi būti tekstas.',
            'order.integer' => 'Darbotvarkės klausimo tvarka turi būti skaičius.',
            'order.min' => 'Darbotvarkės klausimo tvarka turi būti bent 1.',
            'type.in' => 'Punkto tipas turi būti vienas iš: voting, informational, deferred.',
            'student_position.max' => 'Studentų pozicija negali būti ilgesnė nei 5000 simbolių.',
        ];
    }
}
