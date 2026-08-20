<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'channels' => 'nullable|array',
            'channels.*' => 'nullable|array',
            'channels.*.*' => 'boolean',
            'digest_frequency_hours' => 'nullable|integer|min:1|max:24',
            'digest_emails' => 'nullable|array',
            'digest_emails.*' => 'email',
            'muted_until' => 'nullable|date',
            'reminder_settings' => 'nullable|array',
            'reminder_settings.task_reminder_days' => 'nullable|array',
            'reminder_settings.task_reminder_days.*' => 'integer|min:1',
            'reminder_settings.meeting_reminder_hours' => 'nullable|array',
            'reminder_settings.meeting_reminder_hours.*' => 'integer|min:1',
            'reminder_settings.calendar_reminder_hours' => 'nullable|array',
            'reminder_settings.calendar_reminder_hours.*' => 'integer|min:1',
        ];
    }
}
