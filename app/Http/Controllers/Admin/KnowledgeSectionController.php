<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\KnowledgeSectionRequest;
use App\Models\KnowledgeSection;
use Illuminate\Http\RedirectResponse;

class KnowledgeSectionController extends AdminController
{
    public function store(KnowledgeSectionRequest $request): RedirectResponse
    {
        $this->authorize('create', KnowledgeSection::class);
        KnowledgeSection::create([...$request->safe()->only(['title', 'description', 'order', 'tenant_id']), 'created_by' => $request->user()->id]);

        return back()->with('success', __('learning.sections.created'));
    }

    public function update(KnowledgeSectionRequest $request, KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $this->authorize('update', $knowledgeSection);
        $knowledgeSection->update($request->safe()->only(['title', 'description', 'order', 'tenant_id']));

        return back()->with('success', __('learning.sections.updated'));
    }

    public function destroy(KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $this->authorize('delete', $knowledgeSection);
        $knowledgeSection->delete();

        return back()->with('success', __('learning.sections.deleted'));
    }
}
