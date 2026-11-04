<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The old editor URL forwards its deep-link state to the record page.
 */
class EditAgendaItemRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        $agendaItem = $this->route('agendaItem');
        abort_if($agendaItem->meeting === null, 404);

        return $this->user()?->can('viewSummary', $agendaItem) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'focus' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', 'string', 'max:64'],
        ];
    }
}
