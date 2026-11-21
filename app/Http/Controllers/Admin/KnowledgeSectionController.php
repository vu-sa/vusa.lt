<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\KnowledgeSectionRequest;
use App\Http\Requests\ReorderKnowledgeSectionItemsRequest;
use App\Http\Requests\ReorderKnowledgeSectionsRequest;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Services\KnowledgeBasePresenter;
use App\Services\KnowledgeSectionOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeSectionController extends AdminController
{
    #[Authorize('viewAny', KnowledgeItem::class)]
    public function show(Request $request, KnowledgeSection $knowledgeSection, KnowledgeSectionOptions $sections): Response
    {
        $canUpdate = $request->user()->can('update', $knowledgeSection);

        return Inertia::render('Admin/Knowledge/ShowKnowledgeSection', [
            ...KnowledgeBasePresenter::sectionPage($knowledgeSection, app()->getLocale()),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
            'canReorder' => $canUpdate,
            'editing' => $canUpdate ? ['options' => $sections->options($request->user(), 'update', $knowledgeSection), 'scopes' => $sections->scopes($request->user())] : null,
        ]);
    }

    #[Authorize('update', 'knowledgeSection')]
    public function reorderItems(ReorderKnowledgeSectionItemsRequest $request, KnowledgeSection $knowledgeSection): RedirectResponse
    {
        DB::transaction(function () use ($request, $knowledgeSection): void {
            $items = $knowledgeSection->items()->get()->keyBy('id');
            foreach ($request->validated('items') as $order => $id) {
                $items->get($id)?->update(['order' => $order]);
            }
        });

        return back()->with('success', __('learning.sections.reordered_items'));
    }

    #[Authorize('create', KnowledgeSection::class)]
    public function store(KnowledgeSectionRequest $request): RedirectResponse
    {
        KnowledgeSection::create([
            'order' => (int) KnowledgeSection::query()->max('order') + 1,
            ...$request->safe()->only(['title', 'description', 'kind', 'date', 'order', 'tenant_id']),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', __('learning.sections.created'));
    }

    #[Authorize('update', 'knowledgeSection')]
    public function update(KnowledgeSectionRequest $request, KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $knowledgeSection->update([...$request->safe()->only(['title', 'description', 'kind', 'order', 'tenant_id']), 'date' => $request->validated('date')]);

        return back()->with('success', __('learning.sections.updated'));
    }

    // Authorized in ReorderKnowledgeSectionsRequest: every selected section.
    public function reorder(ReorderKnowledgeSectionsRequest $request): RedirectResponse
    {
        $sections = $request->sections()->keyBy('id');
        foreach ($request->validated('sections') as $position) {
            $sections->get($position['id'])?->update(['order' => $position['order']]);
        }

        return back()->with('success', __('learning.sections.reordered'));
    }

    #[Authorize('delete', 'knowledgeSection')]
    public function destroy(KnowledgeSection $knowledgeSection): RedirectResponse
    {
        $knowledgeSection->delete();

        return back()->with('success', __('learning.sections.deleted'));
    }
}
