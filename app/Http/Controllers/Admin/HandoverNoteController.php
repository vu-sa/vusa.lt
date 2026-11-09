<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\HandoverNoteRequest;
use App\Models\Duty;
use App\Models\HandoverNote;
use App\Services\LearningAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HandoverNoteController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HandoverNote::class);

        return Inertia::render('Admin/Learning/ShowHandover', [
            'writable' => LearningAdminPresenter::writable($request->user(), app()->getLocale()),
            'received' => LearningAdminPresenter::received($request->user(), app()->getLocale()),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function store(HandoverNoteRequest $request, Duty $duty): RedirectResponse
    {
        $this->authorize('create', [HandoverNote::class, $duty]);
        $term = $request->user()->dutiables()->where('duty_id', $duty->id)->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()->subDays(60)))->latest('start_date')->first();
        $duty->handoverNotes()->updateOrCreate(['user_id' => $request->user()->id, 'dutiable_id' => $term?->id], ['body' => $request->validated('body')]);

        return back()->with('success', $this->entityMessage('created', 'handoverNote'));
    }

    public function update(HandoverNoteRequest $request, HandoverNote $handoverNote): RedirectResponse
    {
        $this->authorize('update', $handoverNote);
        $handoverNote->update($request->validated());

        return back()->with('success', $this->entityMessage('updated', 'handoverNote'));
    }
}
