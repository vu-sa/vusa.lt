<?php

namespace App\Http\Requests;

use App\Models\KnowledgeSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderKnowledgeSectionItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('knowledgeSection'));
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:500'],
            'items.*' => ['required', 'string', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            /** @var KnowledgeSection $section */
            $section = $this->route('knowledgeSection');
            $ids = $validator->validated()['items'];
            if ($section->items()->whereKey($ids)->count() !== count($ids)) {
                $validator->errors()->add('items', __('learning.validation.section_items'));
            }
        }];
    }
}
