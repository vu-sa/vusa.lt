<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\LearningSectionRequest;
use App\Models\LearningSection;
use Illuminate\Http\RedirectResponse;

class LearningSectionController extends AdminController
{
    public function store(LearningSectionRequest $request): RedirectResponse
    {
        $this->authorize('create', LearningSection::class);
        LearningSection::create([...$request->safe()->only(['title', 'description', 'order', 'tenant_id']), 'created_by' => $request->user()->id]);

        return back()->with('success', __('learning.sections.created'));
    }

    public function update(LearningSectionRequest $request, LearningSection $learningSection): RedirectResponse
    {
        $this->authorize('update', $learningSection);
        $learningSection->update($request->safe()->only(['title', 'description', 'order', 'tenant_id']));

        return back()->with('success', __('learning.sections.updated'));
    }

    public function destroy(LearningSection $learningSection): RedirectResponse
    {
        $this->authorize('delete', $learningSection);
        $learningSection->delete();

        return back()->with('success', __('learning.sections.deleted'));
    }
}
