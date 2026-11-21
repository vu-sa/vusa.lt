<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAgendaItemNoteRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'yjs_state' => ['required', 'string'],
            'notes_html' => ['nullable', 'string'],
            // The version the client last loaded or saved; null when it never loaded the notes.
            'base_version' => ['present', 'nullable', 'integer', 'min:0'],
        ];
    }
}
