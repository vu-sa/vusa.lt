<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Models\KnowledgeItem;
use App\Services\KnowledgeBasePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends AdminController
{
    public function show(Request $request): Response
    {
        $this->authorize('viewAny', KnowledgeItem::class);

        return Inertia::render('Admin/Knowledge/ShowKnowledgeBase', [
            'knowledge' => KnowledgeBasePresenter::overview(ResolveLearningPath::execute($request->user()), app()->getLocale()),
        ]);
    }
}
