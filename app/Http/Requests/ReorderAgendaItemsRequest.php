<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderAgendaItemsRequest extends FormRequest
{
    /**
     * Reordering is a write against the meeting's agenda, so it follows the meeting's update
     * ability. Authorization stays in the controller because it needs the validated
     * meeting_id, and the scoped where() there confines the writes to that meeting's items.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meeting_id' => ['required', SoftDeleteRules::existsLive('meetings')],
            'agenda_items' => 'required|array',
            'agenda_items.*.id' => 'required|exists:agenda_items,id',
            'agenda_items.*.order' => 'required|integer|min:1',
        ];
    }
}
