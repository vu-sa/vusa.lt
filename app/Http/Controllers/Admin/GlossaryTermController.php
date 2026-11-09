<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\GlossaryTermRequest;
use App\Models\GlossaryTerm;
use App\Services\LearningAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlossaryTermController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GlossaryTerm::class);

        return Inertia::render('Admin/Learning/IndexGlossaryTerm', [
            'terms' => LearningAdminPresenter::glossary(app()->getLocale()),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function store(GlossaryTermRequest $request): RedirectResponse
    {
        $this->authorize('create', GlossaryTerm::class);
        GlossaryTerm::create($request->validated());

        return back()->with('success', $this->entityMessage('created', 'glossaryTerm'));
    }

    public function update(GlossaryTermRequest $request, GlossaryTerm $glossaryTerm): RedirectResponse
    {
        $this->authorize('update', $glossaryTerm);
        $glossaryTerm->update($request->validated());

        return back()->with('success', $this->entityMessage('updated', 'glossaryTerm'));
    }

    public function destroy(GlossaryTerm $glossaryTerm): RedirectResponse
    {
        $this->authorize('delete', $glossaryTerm);
        $glossaryTerm->delete();

        return back()->with('success', $this->entityMessage('deleted', 'glossaryTerm'));
    }
}
