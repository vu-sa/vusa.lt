<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
            'sorting' => 'nullable|string',
            'filters' => 'nullable|string',
            'showDeleted' => 'nullable|boolean',
        ];
    }

    /**
     * Get the sorting state from the request.
     */
    public function getSorting(): array
    {
        if (! $this->has('sorting')) {
            return [];
        }

        try {
            return json_decode($this->input('sorting'), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get the filters from the request.
     */
    public function getFilters(): array
    {
        if (! $this->has('filters')) {
            return [];
        }

        try {
            return json_decode($this->input('filters'), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
