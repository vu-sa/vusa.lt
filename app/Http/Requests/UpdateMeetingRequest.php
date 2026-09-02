<?php

namespace App\Http\Requests;

use App\Enums\MeetingType;
use App\Http\Requests\Concerns\NormalizesTranslatableInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
     * The title is derived from start_time + type by MeetingController::buildMeetingTitle(),
     * so it is not accepted from the payload.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_time' => 'required|date',
            'type' => ['nullable', new Enum(MeetingType::class)],
            // Absent until now, so MeetingForm's "Aprašymas" textarea silently discarded
            // every edit to an existing meeting.
            'description' => 'nullable|array',
            'description.lt' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
        ];
    }
}
