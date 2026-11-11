<?php

namespace App\Http\Requests;

use App\Actions\ResolveLearningPath;
use App\Enums\LearningProgressStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeItemProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ResolveLearningPath::execute($this->user())->contains($this->route('knowledgeItem'));
    }

    public function rules(): array
    {
        return ['status' => ['present', 'nullable', Rule::enum(LearningProgressStatus::class)]];
    }
}
