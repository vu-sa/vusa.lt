<?php

namespace App\Http\Requests\Meetings;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Search the unpublished SharePoint files a meeting could link, past the few the page suggests.
 */
class IndexMeetingPendingDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $this->user()?->can('update', $meeting) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
        ];
    }
}
