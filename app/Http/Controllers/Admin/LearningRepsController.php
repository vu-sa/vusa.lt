<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetCoordinatedRepresentatives;
use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Models\LearningStep;
use App\Models\User;
use App\Services\LearningAdminPresenter;
use App\Services\LearningPathPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningRepsController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewReps', LearningStep::class);
        $reps = LearningAdminPresenter::representatives(GetCoordinatedRepresentatives::execute($request->user()), app()->getLocale());

        return Inertia::render('Admin/Learning/IndexLearningReps', [
            'reps' => $reps, 'summary' => LearningAdminPresenter::summary($reps), 'canSeeReps' => true,
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorize('viewRep', [LearningStep::class, $user]);

        return Inertia::render('Admin/Learning/ShowLearningRep', [
            'path' => LearningPathPresenter::path(ResolveLearningPath::execute($user), app()->getLocale()),
            'rep' => LearningAdminPresenter::member($user), 'canSeeReps' => true,
        ]);
    }
}
