<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $vote = $this->route('vote');
        $agendaItem = $vote->agendaItem;

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
            'is_main' => 'nullable|boolean',
            'title' => 'nullable|string|max:500',
            'student_vote' => 'nullable|string|in:positive,negative,neutral',
            'decision' => 'nullable|string|in:positive,negative,neutral',
            'student_benefit' => 'nullable|string|in:positive,negative,neutral',
            'note' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.string' => 'Balsavimo pavadinimas turi būti tekstas.',
            'title.max' => 'Balsavimo pavadinimas negali būti ilgesnis nei 500 simbolių.',
            'student_vote.in' => 'Studentų balsavimo reikšmė turi būti viena iš: positive, negative, neutral.',
            'decision.in' => 'Sprendimo reikšmė turi būti viena iš: positive, negative, neutral.',
            'student_benefit.in' => 'Naudos studentams reikšmė turi būti viena iš: positive, negative, neutral.',
            'note.max' => 'Pastaba negali būti ilgesnė nei 2000 simbolių.',
        ];
    }
}
