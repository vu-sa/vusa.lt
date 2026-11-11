<?php

namespace App\Http\Requests;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Http\Requests\Concerns\ValidatesLearningAudiences;
use App\Models\Document;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Policies\KnowledgeItemPolicy;
use App\Policies\KnowledgeSectionPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KnowledgeItemRequest extends FormRequest
{
    use ValidatesLearningAudiences;

    public function authorize(): bool
    {
        $item = $this->route('knowledgeItem');

        return $this->user()->can($item instanceof KnowledgeItem ? 'update' : 'create', $item ?? KnowledgeItem::class);
    }

    public function rules(): array
    {
        return [
            'knowledge_section_id' => ['nullable', 'exists:knowledge_sections,id'],
            'audiences' => ['required', 'array', 'min:1'],
            'audiences.*' => ['array:id,type,source_id,tenant_id,importance,stage,description,order'],
            'audiences.*.id' => ['nullable', 'string'],
            'audiences.*.type' => ['required', Rule::enum(LearningAudienceType::class)],
            'audiences.*.source_id' => ['nullable', 'string', 'max:26', 'required_unless:audiences.*.type,everyone'],
            'audiences.*.tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'audiences.*.importance' => ['required', Rule::enum(LearningImportance::class)],
            'audiences.*.stage' => ['prohibited'],
            'audiences.*.description' => ['nullable', 'array:lt,en'],
            'audiences.*.description.*' => ['nullable', 'string', 'max:20000'],
            'audiences.*.order' => ['sometimes', 'integer', 'min:0'],
            'title' => ['required', 'array:lt,en'],
            'title.lt' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array:lt,en'],
            'description.*' => ['nullable', 'string', 'max:20000'],
            'per_term' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'reviewed_at' => ['nullable', 'date'],
            'sources' => ['required', 'array:lt,en'],
            'sources.lt' => ['required', 'array:document_id,url'],
            'sources.*' => ['array:document_id,url'],
            'sources.*.document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'sources.*.url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $validator->validated();
            $ability = $this->route('knowledgeItem') ? 'update' : 'create';
            abort_unless(app(KnowledgeItemPolicy::class)->manageAudiences($this->user(), $data['audiences'], $ability), 403);
            if (filled($data['knowledge_section_id'] ?? null)) {
                $section = KnowledgeSection::findOrFail($data['knowledge_section_id']);
                abort_unless(app(KnowledgeSectionPolicy::class)->target($this->user(), $section->tenant_id, $ability), 403);
            }
            $this->validateAudienceSources($validator, $data);
            $this->validateSources($validator, $data['sources']);
        }];
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function validateSources(Validator $validator, array $sources): void
    {
        foreach ($sources as $locale => $source) {
            if (filled($source['document_id'] ?? null) === filled($source['url'] ?? null)) {
                $validator->errors()->add("sources.{$locale}", __('learning.validation.source'));
            }
            if (filled($source['document_id'] ?? null)) {
                $document = Document::find($source['document_id']);
                if ($document !== null && ! $this->user()->can('view', $document)) {
                    abort(403);
                }
            }
        }
    }
}
