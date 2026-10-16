<?php

namespace App\Http\Requests\MailingLists;

class UpdateMailingListRequest extends MailingListRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('mailingList'));
    }
}
