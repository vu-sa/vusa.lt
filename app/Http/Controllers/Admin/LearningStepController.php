<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetUserCoordinators;
use App\Actions\ResolveLearningPath;
use App\Http\Controllers\AdminController;
use App\Http\Requests\LearningStepRequest;
use App\Models\LearningStep;
use App\Services\Learning\ResolvedLearningStep;
use App\Services\LearningAdminPresenter;
use App\Services\LearningInstitutionContactsPresenter;
use App\Services\LearningPathPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LearningStepController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LearningStep::class);

        return Inertia::render('Admin/Learning/IndexLearningStep', [
            'steps' => LearningPathPresenter::library(app()->getLocale()),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function show(Request $request, LearningStep $learningStep): Response
    {
        $this->authorize('view', $learningStep);
        $learningStep->load(['sources.document.institution.tenant', 'institutionType', 'dutyType', 'tenant', 'glossaryTerms'])->loadCount('comments');
        $path = ResolveLearningPath::execute($request->user());
        $item = $path->steps->concat($path->meetingPrep)->first(fn (ResolvedLearningStep $item): bool => $item->step->id === $learningStep->id) ?? new ResolvedLearningStep($learningStep);

        return Inertia::render('Admin/Learning/ShowLearningStep', [
            'step' => LearningPathPresenter::step($item, $request->user(), app()->getLocale()),
            'inPath' => $path->contains($learningStep->id),
            'coordinators' => GetUserCoordinators::execute($request->user()),
            'institutionContacts' => LearningInstitutionContactsPresenter::forUser($request->user(), app()->getLocale()),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LearningStep::class);

        return Inertia::render('Admin/Learning/CreateLearningStep', ['canSeeReps' => LearningAdminPresenter::canSeeReps($request->user())]);
    }

    public function edit(Request $request, LearningStep $learningStep): Response
    {
        $this->authorize('update', $learningStep);

        return Inertia::render('Admin/Learning/EditLearningStep', [
            'step' => LearningPathPresenter::edit($learningStep->load('sources')),
            'canSeeReps' => LearningAdminPresenter::canSeeReps($request->user()),
        ]);
    }

    public function store(LearningStepRequest $request): RedirectResponse
    {
        $this->authorize('create', LearningStep::class);
        $step = DB::transaction(function () use ($request): LearningStep {
            $step = LearningStep::create([...$request->safe()->except('sources'), 'created_by' => $request->user()->id]);
            $this->syncSources($step, $request->validated('sources', []));

            return $step;
        });

        return to_route('learningSteps.show', $step)->with('success', $this->entityMessage('created', 'learningStep'));
    }

    public function update(LearningStepRequest $request, LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('update', $learningStep);
        DB::transaction(function () use ($request, $learningStep): void {
            $learningStep->update($request->safe()->except('sources'));
            if (! $learningStep->is_system && array_key_exists('sources', $request->validated())) {
                $this->syncSources($learningStep, $request->validated('sources'));
            }
        });

        return back()->with('success', $this->entityMessage('updated', 'learningStep'));
    }

    public function destroy(LearningStep $learningStep): RedirectResponse
    {
        $this->authorize('delete', $learningStep);
        $learningStep->delete();

        return to_route('learningSteps.index')->with('success', $this->entityMessage('deleted', 'learningStep'));
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function syncSources(LearningStep $step, array $sources): void
    {
        $step->sources()->whereNotIn('locale', array_keys($sources))->delete();
        foreach ($sources as $locale => $source) {
            $step->sources()->updateOrCreate(['locale' => $locale], ['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
        }
    }
}
