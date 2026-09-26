<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('calendar'));
    }

    public function rules(): array
    {
        return [
            'is_draft' => ['sometimes', 'required', 'boolean'],
            'event_type_id' => ['sometimes', 'nullable', 'integer', SoftDeleteRules::existsLive('event_types')],
        ];
    }
}
