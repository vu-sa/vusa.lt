<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Enums\LearningProgressStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\KnowledgeItemProgressRequest;
use App\Models\KnowledgeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class KnowledgeItemProgressController extends AdminController
{
    public function store(KnowledgeItemProgressRequest $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->authorize('view', $knowledgeItem);
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->knowledge->firstWhere('item.id', $knowledgeItem->id);
        $status = $request->validated('status');
        if (($status === LearningProgressStatus::Skipped->value && ! $item?->importance->isSkippable())) {
            throw ValidationException::withMessages(['status' => __('learning.validation.progress')]);
        }
        $progress = $knowledgeItem->progress()->where('user_id', $request->user()->id);
        if ($status === null) {
            $progress->delete();
        } else {
            $progress->updateOrCreate(['user_id' => $request->user()->id], ['status' => $status, 'completed_at' => now()]);
        }

        return back();
    }
}
