<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\DegreeEnum;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexStudyProgramRequest;
use App\Http\Requests\MergeStudyProgramsRequest;
use App\Http\Requests\StoreStudyProgramRequest;
use App\Http\Requests\UpdateStudyProgramRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StudyProgramController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexStudyProgramRequest $request)
    {
        $this->handleAuthorization('viewAny', StudyProgram::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        $query = StudyProgram::query()->with('tenant:id,shortname')->orderBy('name');
        $query = $this->withForceDeleteBlockers(
            $request->getShowDeleted() ? $query->onlyTrashed() : $query,
            $request,
            ['dutiables'],
        );

        return $this->inertiaResponse('Admin/People/IndexStudyProgram', [
            'studyPrograms' => $this->appendForceDeleteBlockedReason($query->get(), $request)->values(),
            'deletedCount' => StudyProgram::onlyTrashed()->count(),
            'degreeOptions' => DegreeEnum::getFormOptions(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', StudyProgram::class);

        return $this->inertiaResponse('Admin/People/CreateStudyProgram', [
            'tenants' => GetTenantsForUpserts::execute('studyPrograms.create.padalinys', $this->authorizer),
            'degreeOptions' => DegreeEnum::getFormOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudyProgramRequest $request)
    {
        $this->handleAuthorization('create', StudyProgram::class);

        $studyProgram = StudyProgram::create($request->validated());

        return redirect()->route('studyPrograms.index')
            ->with('success', $this->entityMessage('created', 'studyProgram'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudyProgram $studyProgram)
    {
        $this->handleAuthorization('update', $studyProgram);

        return $this->inertiaResponse('Admin/People/EditStudyProgram', [
            'studyProgram' => $studyProgram->load('tenant')->toFullArray(),
            'tenants' => GetTenantsForUpserts::execute('studyPrograms.update.padalinys', $this->authorizer),
            'degreeOptions' => DegreeEnum::getFormOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudyProgramRequest $request, StudyProgram $studyProgram)
    {
        $this->handleAuthorization('update', $studyProgram);

        $studyProgram->update($request->validated());

        return redirect()->route('studyPrograms.index')
            ->with('success', $this->entityMessage('updated', 'studyProgram'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudyProgram $studyProgram)
    {
        $this->handleAuthorization('delete', $studyProgram);

        // Check if the study program is being used by any dutiables
        $dutiablesCount = Dutiable::where('study_program_id', $studyProgram->id)->count();

        if ($dutiablesCount > 0) {
            return back()->with('error', __('messages.study_program.in_use', ['count' => $dutiablesCount]));
        }

        $studyProgram->delete();

        return redirect()->route('studyPrograms.index')
            ->with('success', $this->entityMessage('deleted', 'studyProgram'));
    }

    /**
     * Merge multiple study programs into one.
     */
    public function mergeStudyPrograms(MergeStudyProgramsRequest $request)
    {
        $targetId = $request->validated()['target_study_program_id'];
        $sourceIds = $request->validated()['source_study_program_ids'];

        DB::transaction(function () use ($targetId, $sourceIds): void {
            $targetStudyProgram = StudyProgram::findOrFail($targetId);
            $sourceStudyPrograms = StudyProgram::whereIn('id', $sourceIds)->get();

            foreach ($sourceStudyPrograms as $sourceStudyProgram) {
                // Transfer all dutiables from source to target
                Dutiable::where('study_program_id', $sourceStudyProgram->id)
                    ->update(['study_program_id' => $targetId]);

                // Delete the source study program
                $sourceStudyProgram->delete();
            }
        });

        return redirect()->route('studyPrograms.index')
            ->with('success', __('messages.study_program.merged'));
    }

    public function restore(StudyProgram $studyProgram): RedirectResponse
    {
        return $this->restoreModel($studyProgram);
    }

    public function forceDelete(StudyProgram $studyProgram): RedirectResponse
    {
        return $this->forceDeleteModel($studyProgram);
    }
}
