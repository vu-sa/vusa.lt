<?php

namespace App\Http\Requests;

use App\Models\Pivots\AgendaItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgendaItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', AgendaItem::class);
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
            'agendaItemTitles.*' => 'required|string|max:255',
            'meeting_id' => 'required|ulid|exists:meetings,id',
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
            'agendaItemTitles.required' => 'Bent vienas darbotvarkės klausimas turi būti pridėtas.',
            'agendaItemTitles.*.required' => 'Darbotvarkės klausimas negali būti tuščias.',
        ];
    }
}
