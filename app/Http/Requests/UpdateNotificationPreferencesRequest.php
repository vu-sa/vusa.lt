<?php

namespace App\Http\Requests;

use App\Enums\EmailDelivery;
use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Self-scoped: only the acting user's own notification preferences are written.
 */
class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $configurable = array_map(fn (NotificationType $type): string => $type->value, NotificationType::configurable());

        return [
            'types' => ['sometimes', 'array:'.implode(',', $configurable)],
            'types.*' => ['array'],
            'types.*.email' => ['sometimes', Rule::enum(EmailDelivery::class)],
            'types.*.push' => ['sometimes', 'boolean'],
            'digest_frequency_hours' => ['sometimes', 'integer', Rule::in(User::DIGEST_FREQUENCY_OPTIONS)],
            'emails' => ['sometimes', 'array'],
            'emails.*' => ['email'],
            'reminder_settings' => ['sometimes', 'array'],
            'reminder_settings.task_reminder_days' => ['sometimes', 'array'],
            'reminder_settings.task_reminder_days.*' => ['integer', Rule::in(User::TASK_REMINDER_DAY_OPTIONS)],
            'reminder_settings.meeting_reminder_hours' => ['sometimes', 'array'],
            'reminder_settings.meeting_reminder_hours.*' => ['integer', Rule::in(User::MEETING_REMINDER_HOUR_OPTIONS)],
        ];
    }
}
