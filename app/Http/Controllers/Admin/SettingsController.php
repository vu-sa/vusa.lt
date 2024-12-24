<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFormSettingsRequest;
use App\Models\Form;
use App\Settings\FormSettings;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show form settings.
     */
    public function editFormSettings(FormSettings $settings)
    {
        return Inertia::render('Admin/Settings/EditFormSettings', [
            'member_registration_form_id' => $settings->member_registration_form_id,
            'forms' => Form::all(['id', 'name']),
        ]);
    }

    /**
     * Update form settings.
     */
    public function updateFormSettings(UpdateFormSettingsRequest $request, FormSettings $settings)
    {
        $settings->member_registration_form_id = $request->member_registration_form_id;

        $settings->save();

        return back()->with('success', 'Form settings updated.');
    }
}
