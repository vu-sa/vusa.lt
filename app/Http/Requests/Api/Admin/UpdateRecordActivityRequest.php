<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecordActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('institution') ?? $this->route('duty'));
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
