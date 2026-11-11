<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\KnowledgeSectionRequest;
use App\Http\Requests\ReorderKnowledgeSectionItemsRequest;
use App\Http\Requests\ReorderKnowledgeSectionsRequest;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeSectionController extends AdminController
{
    public function show(Request $request, KnowledgeSection $knowledgeSection): Response
    {
        $this->authorize('viewAny', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/ShowKnowledgeSection', [
            ...KnowledgeBasePresenter::sectionPage($knowledgeSection, app()->getLocale()),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
            'canReorder' => $request->user()->can('update', $knowledgeSection),
        ]);
    }

    public function reorderItems(ReorderKnowledgeSectionItemsRequest $request, KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $this->authorize('update', $knowledgeSection);
        DB::transaction(function () use ($request, $knowledgeSection): void {
            $items = $knowledgeSection->items()->get()->keyBy('id');
            foreach ($request->validated('items') as $order => $id) {
                $items->get($id)?->update(['order' => $order]);
            }
        });

        return back()->with('success', __('learning.sections.reordered_items'));
    }

    public function store(KnowledgeSectionRequest $request): RedirectResponse
    {
        $this->authorize('create', KnowledgeSection::class);
        KnowledgeSection::create([
            'order' => (int) KnowledgeSection::query()->max('order') + 1,
            ...$request->safe()->only(['title', 'description', 'kind', 'date', 'order', 'tenant_id']),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', __('learning.sections.created'));
    }

    public function update(KnowledgeSectionRequest $request, KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $this->authorize('update', $knowledgeSection);
        $knowledgeSection->update([...$request->safe()->only(['title', 'description', 'kind', 'order', 'tenant_id']), 'date' => $request->validated('date')]);

        return back()->with('success', __('learning.sections.updated'));
    }

    public function reorder(ReorderKnowledgeSectionsRequest $request): RedirectResponse
    {
        $sections = $request->sections()->keyBy('id');
        foreach ($request->validated('sections') as $position) {
            $sections->get($position['id'])?->update(['order' => $position['order']]);
        }

        return back()->with('success', __('learning.sections.reordered'));
    }

    public function destroy(KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $this->authorize('delete', $knowledgeSection);
        $knowledgeSection->delete();

        return back()->with('success', __('learning.sections.deleted'));
    }
}
