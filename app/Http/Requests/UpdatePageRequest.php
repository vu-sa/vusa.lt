<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesContentParts;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    use ValidatesContentParts;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->page);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->contentPartRules(),
            'title' => 'required|string|max:255',
            'lang' => 'required|string|in:lt,en',
            'permalink' => ['sometimes', 'required', 'string', 'max:255', UniqueAmongTrashed::of('pages')->ignore($this->page->id)],
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
            // `different:id` was inert — the payload has no `id` field — so a page
            // could be paired with itself. Compare against the route model instead.
            'other_lang_id' => ['nullable', SoftDeleteRules::existsLive('pages'), Rule::notIn([$this->page->id])],
            'is_active' => 'required|boolean',
            'layout' => 'nullable|string|in:default,wide,focused',
            'show_table_of_contents' => ['boolean'],
            'show_title' => ['boolean'],
            'show_breadcrumbs' => ['boolean'],
        ];
    }
}
