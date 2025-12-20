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
            'decision' => 'nullable|string|in:positive,negative,neutral',
            'student_vote' => 'nullable|string|in:positive,negative,neutral',
            'student_benefit' => 'nullable|string|in:positive,negative,neutral',
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
            'decision.in' => 'Sprendimo reikšmė turi būti viena iš: positive, negative, neutral.',
            'student_vote.in' => 'Studentų balsavimo reikšmė turi būti viena iš: positive, negative, neutral.',
            'student_benefit.in' => 'Naudos studentams reikšmė turi būti viena iš: positive, negative, neutral.',
        ];
    }
}
