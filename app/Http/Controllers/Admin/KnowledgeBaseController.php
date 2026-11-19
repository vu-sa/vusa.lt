<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Http\Requests\ShowKnowledgeBaseRequest;
use App\Models\KnowledgeItem;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends AdminController
{
    #[Authorize('viewAny', KnowledgeItem::class)]
    public function show(ShowKnowledgeBaseRequest $request): Response|RedirectResponse
    {
        $view = $request->validated('view');
        if (in_array($view, ['importance', 'sections', 'mine'], true)) {
            return to_route('knowledgeBase.mine', ['view' => $view === 'sections' ? 'sections' : 'importance']);
        }
        $locale = app()->getLocale();

        return Inertia::render('Admin/Knowledge/ShowKnowledgeBase', [
            'knowledge' => KnowledgeBasePresenter::overview(ResolveLearningPath::execute($request->user()), $locale),
            'index' => KnowledgeBasePresenter::index($locale),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
            'sections' => KnowledgeBasePresenter::sections($locale),
            'canCreate' => $request->user()->can('create', KnowledgeItem::class),
        ]);
    }

    #[Authorize('viewAny', KnowledgeItem::class)]
    public function alphabet(Request $request): Response
    {
        return Inertia::render('Admin/Knowledge/IndexKnowledgeAlphabet', [
            'index' => KnowledgeBasePresenter::index(app()->getLocale(), terms: true),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
        ]);
    }

    #[Authorize('viewAny', KnowledgeItem::class)]
    public function graph(Request $request): Response
    {
        return Inertia::render('Admin/Knowledge/ShowKnowledgeGraph', [
            'graph' => KnowledgeBasePresenter::graph(app()->getLocale()),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
        ]);
    }

    #[Authorize('viewAny', KnowledgeItem::class)]
    public function mine(Request $request): Response
    {
        $path = ResolveLearningPath::execute($request->user());

        return Inertia::render('Admin/Knowledge/ShowKnowledgeForYou', [
            'knowledge' => KnowledgeBasePresenter::overview($path, app()->getLocale()),
            'assignedProgress' => KnowledgeBasePresenter::assignedProgress($path),
        ]);
    }
}
