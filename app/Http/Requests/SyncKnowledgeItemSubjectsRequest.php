<?php

namespace App\Http\Requests;

use App\Models\KnowledgeItemSubject;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncKnowledgeItemSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('knowledgeItem'));
    }

    public function rules(): array
    {
        return [
            'subjects' => ['present', 'array', 'max:50'],
            'subjects.*' => ['array:type,id'],
            'subjects.*.type' => ['required', Rule::in(KnowledgeItemSubject::SUBJECT_TYPES)],
            'subjects.*.id' => ['required', 'string', 'max:26'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ($validator->validated()['subjects'] as $index => $subject) {
                $model = Relation::getMorphedModel($subject['type']);
                if ($model === null || ! $model::query()->whereKey($subject['id'])->exists()) {
                    $validator->errors()->add("subjects.{$index}.id", __('validation.exists', ['attribute' => 'id']));
                }
            }
        }];
    }
}
