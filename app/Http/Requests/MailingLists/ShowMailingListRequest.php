<?php

namespace App\Http\Requests\MailingLists;

use Illuminate\Foundation\Http\FormRequest;

class ShowMailingListRequest extends FormRequest
{
    public function rules(): array
    {
        return ['messages_page' => ['sometimes', 'integer', 'min:1']];
    }
}
