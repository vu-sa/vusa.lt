<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetUserCoordinators;
use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Models\LearningStep;
use App\Services\LearningInstitutionContactsPresenter;
use App\Services\LearningPathPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningPathController extends AdminController
{
    public function show(Request $request): Response
    {
        $this->authorize('viewAny', LearningStep::class);

        return Inertia::render('Admin/Learning/ShowLearningPath', [
            'path' => LearningPathPresenter::path(ResolveLearningPath::execute($request->user()), app()->getLocale()),
            'coordinators' => GetUserCoordinators::execute($request->user()),
            'institutionContacts' => LearningInstitutionContactsPresenter::forUser($request->user(), app()->getLocale()),
        ]);
    }
}
