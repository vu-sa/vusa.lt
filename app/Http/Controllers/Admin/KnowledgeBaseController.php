<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends AdminController
{
    public function show(Request $request): Response
    {
        $this->authorize('viewAny', KnowledgeItem::class);
        $locale = app()->getLocale();

        return Inertia::render('Admin/Knowledge/ShowKnowledgeBase', [
            'knowledge' => KnowledgeBasePresenter::overview(ResolveLearningPath::execute($request->user()), $locale),
            'index' => KnowledgeBasePresenter::index($locale),
            'sections' => KnowledgeSection::query()->orderBy('order')->orderBy('id')->get()->map(fn (KnowledgeSection $section): array => [
                'id' => $section->id, 'title' => (string) $section->getTranslation('title', $locale),
                'description' => (string) $section->getTranslation('description', $locale), 'order' => $section->order,
            ])->all(),
            'canCreate' => $request->user()->can('create', KnowledgeItem::class),
        ]);
    }

    public function alphabet(): Response
    {
        $this->authorize('viewAny', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/IndexKnowledgeAlphabet', [
            'index' => KnowledgeBasePresenter::index(app()->getLocale()),
        ]);
    }
}
