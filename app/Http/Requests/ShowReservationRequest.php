<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The record page reloads `allResources` with the range being edited, so the resource form can
 * show capacity without counting the reservation resource it is changing.
 */
class ShowReservationRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('reservation')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dateTimeRange' => ['sometimes', 'array'],
            'dateTimeRange.start' => ['required_with:dateTimeRange', 'integer', 'lte:dateTimeRange.end'],
            'dateTimeRange.end' => ['required_with:dateTimeRange', 'integer'],
            'except-reservations' => ['sometimes', 'array'],
            'except-reservations.*' => ['nullable', 'string'],
            'except-resources' => ['sometimes', 'array'],
            'except-resources.*' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function exceptIds(string $key): array
    {
        return collect($this->validated($key, []))->filter()->unique()->values()->all();
    }
}
