<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Navigation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveNavigationUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Navigation::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'collection' => ['required', 'string', Rule::in(['pages', 'news', 'calendar', 'institutions', 'documents', 'categories'])],
            'id' => ['required'],
        ];
    }
}
