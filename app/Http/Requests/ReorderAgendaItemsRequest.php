<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderAgendaItemsRequest extends FormRequest
{
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
