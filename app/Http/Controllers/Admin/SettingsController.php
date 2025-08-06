<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateFormSettingsRequest;
use App\Http\Requests\UpdateSharepointSettingsRequest;
use App\Models\Form;
use App\Models\Role;
use App\Settings\FormSettings;
use App\Settings\SharepointSettings;

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
     * Show SharePoint settings.
     */
    public function editSharepointSettings(SharepointSettings $settings)
    {
        return $this->inertiaResponse('Admin/Settings/EditSharepointSettings', [
            'permission_expiry_days' => $settings->permission_expiry_days,
            'default_folder_structure' => $settings->default_folder_structure,
        ]);
    }

    /**
     * Update SharePoint settings.
     */
    public function updateSharepointSettings(UpdateSharepointSettingsRequest $request, SharepointSettings $settings)
    {
        $settings->permission_expiry_days = $request->permission_expiry_days;
        $settings->default_folder_structure = $request->default_folder_structure;

        $settings->save();

        return $this->redirectBackWithSuccess('SharePoint settings updated.');
    }
}
