<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Http\Requests\ShowKnowledgeBaseRequest;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends AdminController
{
    public function show(ShowKnowledgeBaseRequest $request): Response|RedirectResponse
    {
        $this->authorize('viewAny', KnowledgeItem::class);
        $view = $request->validated('view');
        if (in_array($view, ['importance', 'sections', 'mine'], true)) {
            return to_route('knowledgeBase.mine', ['view' => $view === 'sections' ? 'sections' : 'importance']);
        }
        $locale = app()->getLocale();

        return Inertia::render('Admin/Knowledge/ShowKnowledgeBase', [
            'knowledge' => KnowledgeBasePresenter::overview(ResolveLearningPath::execute($request->user()), $locale),
            'index' => KnowledgeBasePresenter::index($locale),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
            'sections' => KnowledgeSection::query()->orderBy('order')->orderBy('id')->get()->map(fn (KnowledgeSection $section): array => [
                'id' => $section->id, 'title' => (string) $section->getTranslation('title', $locale),
                'description' => (string) $section->getTranslation('description', $locale), 'order' => $section->order,
            ])->all(),
            'canCreate' => $request->user()->can('create', KnowledgeItem::class),
        ]);
    }

    public function alphabet(Request $request): Response
    {
        $this->authorize('viewAny', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/IndexKnowledgeAlphabet', [
            'index' => KnowledgeBasePresenter::index(app()->getLocale()),
            'doneIds' => KnowledgeBasePresenter::doneIds($request->user()),
        ]);
    }

    public function mine(Request $request): Response
    {
        $this->authorize('viewAny', KnowledgeItem::class);
        $path = ResolveLearningPath::execute($request->user());

        return Inertia::render('Admin/Knowledge/ShowKnowledgeForYou', [
            'knowledge' => KnowledgeBasePresenter::overview($path, app()->getLocale()),
            'assignedProgress' => KnowledgeBasePresenter::assignedProgress($path),
        ]);
    }
}
