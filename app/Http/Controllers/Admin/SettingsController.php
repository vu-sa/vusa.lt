<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateFormSettingsRequest;
use App\Http\Requests\UpdateMeetingSettingsRequest;
use App\Http\Requests\UpdateSettingsAuthorizationRequest;
use App\Models\Form;
use App\Models\Role;
use App\Models\Type;
use App\Settings\FormSettings;
use App\Settings\MeetingSettings;
use App\Settings\SettingsSettings;
use Illuminate\Http\Request;

class SettingsController extends AdminController
{
    /**
     * Check if the current user can manage settings.
     */
    private function authorizeSettingsAccess(SettingsSettings $settings): void
    {
        $user = request()->user();

        abort_if(! $settings->canUserManageSettings($user), 403, __('settings.messages.unauthorized'));
    }

    /**
     * Show settings index page.
     */
    public function index(SettingsSettings $settings)
    {
        $this->authorizeSettingsAccess($settings);

        $user = request()->user();

        return $this->inertiaResponse('Admin/Settings/IndexSettings', [
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * Show form settings.
     */
    public function editFormSettings(FormSettings $formSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        return $this->inertiaResponse('Admin/Settings/EditFormSettings', [
            'member_registration_form_id' => $formSettings->member_registration_form_id,
            'member_registration_notification_recipient_role_id' => $formSettings->member_registration_notification_recipient_role_id,
            'forms' => Form::all(['id', 'name']),
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    /**
     * Update form settings.
     */
    public function updateFormSettings(UpdateFormSettingsRequest $request, FormSettings $formSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        $formSettings->member_registration_form_id = $request->member_registration_form_id;
        $formSettings->member_registration_notification_recipient_role_id = $request->member_registration_notification_recipient_role_id;

        $formSettings->save();

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Show meeting settings.
     */
    public function editMeetingSettings(MeetingSettings $meetingSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        return $this->inertiaResponse('Admin/Settings/EditMeetingSettings', [
            'selected_type_ids' => $meetingSettings->getPublicMeetingInstitutionTypeIds()->toArray(),
            'available_types' => Type::query()
                ->where('model_type', 'App\\Models\\Institution')
                ->get(['id', 'title', 'slug'])
                ->map->toArray(),
        ]);
    }

    /**
     * Update meeting settings.
     */
    public function updateMeetingSettings(UpdateMeetingSettingsRequest $request, MeetingSettings $meetingSettings, SettingsSettings $settingsSettings)
    {
        $this->authorizeSettingsAccess($settingsSettings);

        $meetingSettings->setPublicMeetingInstitutionTypeIds($request->input('type_ids', []));
        $meetingSettings->save();

        return $this->redirectBackWithSuccess(__('settings.messages.updated'));
    }

    /**
     * Show settings authorization page (Super Admin only).
     */
    public function editAuthorization(SettingsSettings $settings)
    {
        $user = request()->user();

        abort_if(! $user->isSuperAdmin(), 403);

        // Filter out Super Admin role - they can always manage settings anyway
        $roles = Role::where('name', '!=', config('permission.super_admin_role_name'))
            ->get(['id', 'name']);

        return $this->inertiaResponse('Admin/Settings/EditSettingsAuthorization', [
            'settings_manager_role_id' => $settings->settings_manager_role_id,
            'roles' => $roles,
        ]);
    }

    /**
     * Update settings authorization (Super Admin only).
     */
    public function updateAuthorization(UpdateSettingsAuthorizationRequest $request, SettingsSettings $settings)
    {
        $user = request()->user();

        abort_if(! $user->isSuperAdmin(), 403);

        $settings->settings_manager_role_id = $request->input('settings_manager_role_id');
        $settings->save();

        return $this->redirectBackWithSuccess(__('settings.messages.authorization_updated'));
    }
}
