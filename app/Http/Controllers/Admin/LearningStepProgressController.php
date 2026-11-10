<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStepKind;
use App\Http\Controllers\AdminController;
use App\Http\Requests\LearningStepProgressRequest;
use App\Models\LearningStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class LearningStepProgressController extends AdminController
{
    public function store(LearningStepProgressRequest $request, LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('view', $learningStep);
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->steps->concat($path->documents)->firstWhere('step.id', $learningStep->id);
        $status = $request->validated('status');
        if ($learningStep->kind === LearningStepKind::MeetingPrep || ($status === LearningProgressStatus::Skipped->value && ! $item?->importance->isSkippable())) {
            throw ValidationException::withMessages(['status' => __('learning.validation.progress')]);
        }
        $progress = $request->user()->learningProgress();
        if ($status === null) {
            $progress->where('learning_step_id', $learningStep->id)->delete();
        } else {
            $progress->updateOrCreate(['learning_step_id' => $learningStep->id], ['status' => $status, 'completed_at' => now()]);
        }

        return back();
    }
}
