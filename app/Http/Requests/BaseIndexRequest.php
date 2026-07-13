<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseIndexRequest extends FormRequest
{
    /**
     * Default sorting applied when no explicit sorting is requested.
     * Override in child classes, e.g.:
     *   protected array $defaultSorting = [['id' => 'created_at', 'desc' => true]];
     *
     * @var array<int, array{id: string, desc: bool}>
     */
    protected array $defaultSorting = [];

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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
            'sorting' => 'nullable|string',
            'filters' => 'nullable|string',
            'showDeleted' => 'nullable|string|in:true,false',
        ];
    }

    /**
     * Get the sorting state from the request.
     * Falls back to {@see $defaultSorting} when the request carries no sorting param.
     */
    public function getSorting(): array
    {
        if ($this->has('sorting')) {
            try {
                $decoded = json_decode($this->input('sorting'), true);

                return is_array($decoded) && ! empty($decoded) ? $decoded : $this->defaultSorting;
            } catch (\Exception $e) {
                return $this->defaultSorting;
            }
        }

        return $this->defaultSorting;
    }

    /**
     * Get the filters from the request.
     * Includes the standalone 'search' parameter so the frontend
     * can pre-populate the search input on back-navigation.
     */
    public function getFilters(): array
    {
        $filters = [];

        if ($this->has('filters')) {
            try {
                $filters = json_decode($this->input('filters'), true) ?? [];
            } catch (\Exception $e) {
                $filters = [];
            }
        }

        // Merge standalone search so it survives round-trips
        if ($this->filled('search')) {
            $filters['search'] = $this->input('search');
        }

        return $filters;
    }
}
