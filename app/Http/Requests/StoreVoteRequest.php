<?php

namespace App\Http\Requests;

use App\Models\Pivots\AgendaItem;
use Illuminate\Foundation\Http\FormRequest;

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
            'agenda_item_id.required' => 'Darbotvarkės punktas yra privalomas.',
            'agenda_item_id.exists' => 'Nurodytas darbotvarkės punktas neegzistuoja.',
            'title.string' => 'Balsavimo pavadinimas turi būti tekstas.',
            'title.max' => 'Balsavimo pavadinimas negali būti ilgesnis nei 500 simbolių.',
            'student_vote.in' => 'Studentų balsavimo reikšmė turi būti viena iš: positive, negative, neutral.',
            'decision.in' => 'Sprendimo reikšmė turi būti viena iš: positive, negative, neutral.',
            'student_benefit.in' => 'Naudos studentams reikšmė turi būti viena iš: positive, negative, neutral.',
            'note.max' => 'Pastaba negali būti ilgesnė nei 2000 simbolių.',
        ];
    }
}
