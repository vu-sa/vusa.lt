<?php

namespace App\Http\Requests;

use App\Models\KnowledgeItem;
use Illuminate\Foundation\Http\FormRequest;

class ShowKnowledgeBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', KnowledgeItem::class);
    }

    public function rules(): array
    {
        return ['view' => ['nullable', 'string', 'max:32']];
    }
}
