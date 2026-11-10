<?php

namespace App\Http\Requests;

use App\Models\LearningSection;
use App\Policies\LearningSectionPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class LearningSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('learningSection');

        return $this->user()->can($section instanceof LearningSection ? 'update' : 'create', $section ?? LearningSection::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array:lt,en'],
            'title.lt' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array:lt,en'],
            'description.*' => ['nullable', 'string', 'max:20000'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $validator->validated();
            $tenantId = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->route('learningSection')?->tenant_id;
            abort_unless(app(LearningSectionPolicy::class)->target($this->user(), $tenantId, $this->route('learningSection') ? 'update' : 'create'), 403);
        }];
    }
}
