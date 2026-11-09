<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Actions\ResolveLearningPath;
use App\Enums\LearningProgressStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\HandoverNoteRequest;
use App\Models\Duty;
use App\Models\HandoverNote;
use App\Models\LearningStepProgress;
use App\Services\LearningAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HandoverNoteController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', HandoverNote::class);
        $user = $request->user();
        $received = LearningAdminPresenter::received($user, app()->getLocale());
        if ($received !== []) {
            $path = ResolveLearningPath::execute($user);
            $step = $path->steps->first(fn ($item): bool => $item->step->key === 'read_handover');
            if ($step !== null && $step->state !== 'done') {
                LearningStepProgress::query()->updateOrCreate(
                    ['user_id' => $user->id, 'learning_step_id' => $step->step->id],
                    ['status' => LearningProgressStatus::Done, 'completed_at' => now()],
                );
            }
        }

        return Inertia::render('Admin/Learning/ShowHandover', [
            'writable' => LearningAdminPresenter::writable($request->user(), app()->getLocale()),
            'received' => $received,
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function store(HandoverNoteRequest $request, Duty $duty): RedirectResponse
    {
        $this->authorize('create', [HandoverNote::class, $duty]);
        $term = $request->user()->dutiables()->where('duty_id', $duty->id)->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()->subDays(60)))->latest('start_date')->first();
        abort_if($term === null, 403);
        $cadence = ResolveCadenceForDuty::execute($duty, $term->start_date);
        DB::transaction(function () use ($request, $duty, $term, $cadence): void {
            // The nullable cadence key needs a parent lock to serialize first writes too.
            Duty::withTrashed()->whereKey($duty->id)->lockForUpdate()->firstOrFail();
            $duty->handoverNotes()->updateOrCreate(
                ['user_id' => $request->user()->id, 'cadence_id' => $cadence?->id],
                [...$request->validated(), 'dutiable_id' => $term->id],
            );
        });

        return back()->with('success', $this->entityMessage('created', 'handoverNote'));
    }

    public function update(HandoverNoteRequest $request, HandoverNote $handoverNote): RedirectResponse
    {
        $this->authorize('update', $handoverNote);
        $handoverNote->update($request->validated());

        return back()->with('success', $this->entityMessage('updated', 'handoverNote'));
    }
}
