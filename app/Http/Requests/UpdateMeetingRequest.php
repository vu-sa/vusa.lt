<?php

namespace App\Http\Requests;

use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Http\Requests\Concerns\NormalizesTranslatableInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateMeetingRequest extends FormRequest
{
    use NormalizesTranslatableInput;

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeTranslatable('description');
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('meeting'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_time' => 'required|date',
            'type' => ['nullable', new Enum(MeetingType::class)],
            'meeting_kind' => ['nullable', Rule::enum(MeetingKind::class)],
            'description' => 'nullable|array',
            'description.lt' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
        ];
    }
}
