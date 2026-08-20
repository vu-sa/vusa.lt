<?php

namespace App\Http\Requests\Dutiables;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The write half. Goes through routes/admin.php rather than the JSON API because
 * AdminController::guardSelfLockout() answers with an `access_change_warning` Inertia
 * flash that useAccessChangeGuard.ts reads off usePage().props.flash.
 */
class ApplyDutiableTimelineRequest extends DutiableTimelineOperationsRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'acknowledge_access_change' => ['nullable', 'boolean'],
        ]);
    }
}
