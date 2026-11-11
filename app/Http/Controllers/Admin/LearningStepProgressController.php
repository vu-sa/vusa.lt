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
        $item = $path->steps->firstWhere('item.id', $learningStep->id);
        $status = $request->validated('status');
        if ($learningStep->kind === LearningStepKind::MeetingPrep || ($status === LearningProgressStatus::Skipped->value && ! $item?->importance->isSkippable())) {
            throw ValidationException::withMessages(['status' => __('learning.validation.progress')]);
        }
        $progress = $learningStep->progress()->where('user_id', $request->user()->id);
        if ($status === null) {
            $progress->delete();
        } else {
            $progress->updateOrCreate(['user_id' => $request->user()->id], ['status' => $status, 'completed_at' => now()]);
        }

        return back();
    }
}
