<?php

namespace App\Http\Requests;

use App\Models\HandoverNote;
use Illuminate\Foundation\Http\FormRequest;

class HandoverNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('handoverNote');

        return $note ? $this->user()->can('update', $note) : $this->user()->can('create', [HandoverNote::class, $this->route('duty')]);
    }

    public function rules(): array
    {
        $fields = ['situation', 'open_issues', 'people', 'resources', 'advice'];
        $rules = [];
        foreach ($fields as $field) {
            $others = array_diff($fields, [$field]);
            $rules[$field] = ['nullable', 'required_without_all:'.implode(',', $others), 'string', 'max:5000'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (['situation', 'open_issues', 'people', 'resources', 'advice'] as $field) {
            $attributes[$field] = __('learning_people.fields.'.$field.'.label');
        }

        return $attributes;
    }
}
