<?php

namespace App\Http\Requests\MailingLists;

use Illuminate\Foundation\Http\FormRequest;

class ShowMailingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('mailingList'));
    }

    public function rules(): array
    {
        return ['messages_page' => ['sometimes', 'integer', 'min:1']];
    }
}
