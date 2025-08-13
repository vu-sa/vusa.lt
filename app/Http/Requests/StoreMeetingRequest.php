<?php

namespace App\Http\Requests;

use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Meeting::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'start_time' => 'required|date',
            'institution_id' => 'required|ulid',
            'type_id' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'agendaItems' => 'nullable|array',
            'agendaItems.*.title' => 'required|string|max:255',
            'agendaItems.*.description' => 'nullable|string|max:1000',
            'agendaItems.*.order' => 'required|integer|min:1',
        ];
    }
}
