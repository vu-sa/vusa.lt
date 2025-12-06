<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateFormSettingsRequest;
use App\Http\Requests\UpdateMeetingSettingsRequest;
use App\Models\Form;
use App\Models\Role;
use App\Models\Type;
use App\Settings\FormSettings;
use App\Settings\MeetingSettings;

class SettingsController extends AdminController
{
    /**
     * Show form settings.
     */
    public function editFormSettings(FormSettings $settings)
    {
        return $this->inertiaResponse('Admin/Settings/EditFormSettings', [
            'member_registration_form_id' => $settings->member_registration_form_id,
            'member_registration_notification_recipient_role_id' => $settings->member_registration_notification_recipient_role_id,
            'forms' => Form::all(['id', 'name']),
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    /**
     * Update form settings.
     */
    public function updateFormSettings(UpdateFormSettingsRequest $request, FormSettings $settings)
    {
        $settings->member_registration_form_id = $request->member_registration_form_id;
        $settings->member_registration_notification_recipient_role_id = $request->member_registration_notification_recipient_role_id;

        $settings->save();

        return $this->redirectBackWithSuccess('Form settings updated.');
    }

    /**
     * Show meeting settings.
     */
    public function editMeetingSettings(MeetingSettings $settings)
    {
        return $this->inertiaResponse('Admin/Settings/EditMeetingSettings', [
            'selected_type_ids' => $settings->getPublicMeetingInstitutionTypeIds()->toArray(),
            'available_types' => Type::query()
                ->where('model_type', 'App\\Models\\Institution')
                ->get(['id', 'title', 'slug'])
                ->map->toArray(),
        ]);
    }

    /**
     * Update meeting settings.
     */
    public function updateMeetingSettings(UpdateMeetingSettingsRequest $request, MeetingSettings $settings)
    {
        $settings->setPublicMeetingInstitutionTypeIds($request->input('type_ids', []));
        $settings->save();

        return $this->redirectBackWithSuccess('Meeting display settings updated.');
    }
}
