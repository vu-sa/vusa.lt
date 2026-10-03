<?php

namespace App\Http\Requests;

use App\Models\Pivots\AgendaItem;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgendaItemProblemRequest extends FormRequest
{
    /**
     * Linking belongs to whoever records the agenda item; every member may already see every problem.
     */
    public function authorize(): bool
    {
        $agendaItem = $this->route('agendaItem');

        return $agendaItem instanceof AgendaItem && $this->user()->can('update', $agendaItem);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'problem_id' => ['required', 'string', SoftDeleteRules::existsLive('problems')],
        ];
    }
}
