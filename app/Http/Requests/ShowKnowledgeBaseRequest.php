<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowKnowledgeBaseRequest extends FormRequest
{
    public function rules(): array
    {
        return ['view' => ['nullable', 'string', 'max:32']];
    }
}
