<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\News;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;

class ContentEditorPairingRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(in_array($this->route('kind'), ['pages', 'news'], true), 404);
        $model = $this->route('kind') === 'pages' ? Page::class : News::class;

        return $this->filled('record_id')
            ? $this->user()->can('update', $model::findOrFail($this->input('record_id')))
            : $this->user()->can('create', $model);
    }

    public function rules(): array
    {
        return [
            'record_id' => ['nullable', 'integer'],
            'target_id' => ['nullable', 'integer'],
            'lang' => ['required', 'in:lt,en'],
        ];
    }
}
