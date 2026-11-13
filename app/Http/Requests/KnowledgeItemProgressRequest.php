<?php

namespace App\Http\Requests;

use App\Enums\LearningProgressStatus;
use App\Models\KnowledgeItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeItemProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('knowledgeItem');

        return $item instanceof KnowledgeItem && $item->is_active && $this->user()->can('view', $item);
    }

    public function rules(): array
    {
        return ['status' => ['present', 'nullable', Rule::enum(LearningProgressStatus::class)]];
    }
}
