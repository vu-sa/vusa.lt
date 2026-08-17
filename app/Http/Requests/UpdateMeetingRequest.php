<?php

namespace App\Http\Requests;

use App\Enums\MeetingType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMeetingRequest extends FormRequest
{
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
        ];
    }
}
