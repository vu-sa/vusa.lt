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
        return ['body' => ['required', 'string', 'max:100000']];
    }
}
