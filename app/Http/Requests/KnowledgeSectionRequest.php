<?php

namespace App\Http\Requests;

use App\Enums\KnowledgeSectionKind;
use App\Models\KnowledgeSection;
use App\Policies\KnowledgeSectionPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KnowledgeSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('knowledgeSection');

        return $this->user()->can($section instanceof KnowledgeSection ? 'update' : 'create', $section ?? KnowledgeSection::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array:lt,en'],
            'title.lt' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array:lt,en'],
            'description.*' => ['nullable', 'string', 'max:20000'],
            'kind' => ['sometimes', Rule::enum(KnowledgeSectionKind::class)],
            'date' => ['nullable', 'date'],
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
            $tenantId = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $this->route('knowledgeSection')?->tenant_id;
            abort_unless(app(KnowledgeSectionPolicy::class)->target($this->user(), $tenantId, $this->route('knowledgeSection') ? 'update' : 'create'), 403);
            $section = $this->route('knowledgeSection');
            $kind = $data['kind'] ?? ($section instanceof KnowledgeSection ? $section->kind->value : KnowledgeSectionKind::Topic->value);
            if (filled($data['date'] ?? null) && $kind !== KnowledgeSectionKind::Collection->value) {
                $validator->errors()->add('date', __('validation.prohibited', ['attribute' => __('learning_library.section_manager.date')]));
            }
            if ($section instanceof KnowledgeSection && isset($data['kind']) && $section->kind->value !== $data['kind'] && $section->items()->exists()) {
                $validator->errors()->add('kind', __('learning.validation.section_kind_locked'));
            }
        }];
    }
}
