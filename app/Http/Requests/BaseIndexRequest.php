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
     * Page size used when the request carries no `per_page`.
     * Override in child classes that want a different default.
     */
    protected int $defaultPerPage = 20;

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
            'sort' => 'nullable|string',
            'filters' => 'nullable|string',
            'showDeleted' => 'nullable|string|in:true,false',
        ];
    }

    /**
     * Get the sorting state from the request: the API's `sorting` JSON, else the collection URL's
     * `sort=field:direction` (so a bookmarked sort holds on the first Inertia render), else
     * {@see $defaultSorting}.
     */
    public function getSorting(): array
    {
        if ($this->has('sorting')) {
            try {
                $decoded = json_decode($this->input('sorting'), true);

                return is_array($decoded) && ! empty($decoded) ? $decoded : $this->defaultSorting;
            } catch (\Exception) {
                return $this->defaultSorting;
            }
        }

        if (is_string($this->query('sort')) && preg_match('/^([\w.]+):(asc|desc)$/', $this->query('sort'), $matches) === 1) {
            return [['id' => $matches[1], 'desc' => $matches[2] === 'desc']];
        }

        return $this->defaultSorting;
    }

    /**
     * Get the page size, read through the validated payload so the `max:100` cap above is
     * always the one that applies.
     */
    public function getPerPage(): int
    {
        $perPage = $this->validated('per_page');

        return $perPage === null ? $this->defaultPerPage : (int) $perPage;
    }

    /**
     * Whether the listing should include soft-deleted records.
     *
     * The param arrives as the string 'true'/'false' (see the rule above), which
     * `$this->boolean()` already handles.
     */
    public function getShowDeleted(): bool
    {
        return $this->boolean('showDeleted');
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
            } catch (\Exception) {
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
