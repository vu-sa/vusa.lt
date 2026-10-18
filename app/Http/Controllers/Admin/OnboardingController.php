<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Services\OnboardingChecklist;
use Illuminate\Http\Request;
use Inertia\Response;

class OnboardingController extends AdminController
{
    public function index(Request $request, OnboardingChecklist $checklist): Response
    {
        return $this->inertiaResponse('Admin/ShowOnboarding', [
            'checklist' => $checklist->present($request->user()),
            'installationTutorials' => config('onboarding.installation_tutorials', []),
        ]);
    }
}
