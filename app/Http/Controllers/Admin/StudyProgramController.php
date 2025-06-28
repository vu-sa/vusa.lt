<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\DegreeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexStudyProgramRequest;
use App\Http\Requests\StoreStudyProgramRequest;
use App\Http\Requests\UpdateStudyProgramRequest;
use App\Http\Requests\MergeStudyProgramsRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudyProgramController extends Controller
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexStudyProgramRequest $request)
    {
        $this->authorize('viewAny', StudyProgram::class);

        // Build base query with eager loading
        $query = StudyProgram::query()->with('tenant');

        // Apply simple filters
        if ($request->has('degree') && !empty($request->degree)) {
            $query->where('degree', $request->degree);
        }

        // Define searchable columns
        $searchableColumns = ['name', 'degree'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        // Paginate results
        $studyPrograms = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        // Get the sorting state using the custom method to ensure consistent parsing
        $sorting = $request->getSorting();

        return Inertia::render('Admin/People/IndexStudyProgram', [
            'studyPrograms' => [
                'data' => $studyPrograms->getCollection()->map->toArray(),
                'meta' => [
                    'total' => $studyPrograms->total(),
                    'per_page' => $studyPrograms->perPage(),
                    'current_page' => $studyPrograms->currentPage(),
                    'last_page' => $studyPrograms->lastPage(),
                    'from' => $studyPrograms->firstItem(),
                    'to' => $studyPrograms->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
            'initialSorting' => $sorting,
            'degreeOptions' => DegreeEnum::getFormOptions(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', StudyProgram::class);

        return Inertia::render('Admin/People/CreateStudyProgram', [
            'tenants' => GetTenantsForUpserts::execute('studyPrograms.create.padalinys', $this->authorizer),
            'degreeOptions' => DegreeEnum::getFormOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudyProgramRequest $request)
    {
        $this->authorize('create', StudyProgram::class);

        $studyProgram = StudyProgram::create($request->validated());

        return redirect()->route('studyPrograms.index')
            ->with('success', 'Study program created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudyProgram $studyProgram)
    {
        $this->authorize('update', $studyProgram);

        return Inertia::render('Admin/People/EditStudyProgram', [
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
        $this->authorize('update', $studyProgram);

        $studyProgram->update($request->validated());

        return redirect()->route('studyPrograms.index')
            ->with('success', 'Study program updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudyProgram $studyProgram)
    {
        $this->authorize('delete', $studyProgram);

        // Check if the study program is being used by any dutiables
        $dutiablesCount = \App\Models\Pivots\Dutiable::where('study_program_id', $studyProgram->id)->count();
        
        if ($dutiablesCount > 0) {
            return back()->with('error', "Cannot delete study program. It is currently assigned to {$dutiablesCount} duty assignment(s).");
        }

        $studyProgram->delete();

        return redirect()->route('studyPrograms.index')
            ->with('success', 'Study program deleted successfully.');
    }

    /**
     * Show the form for merging study programs.
     */
    public function merge()
    {
        $this->authorize('viewAny', StudyProgram::class);

        return Inertia::render('Admin/People/MergeStudyPrograms', [
            'studyPrograms' => StudyProgram::with('tenant')->get()->map->toArray(),
        ]);
    }

    /**
     * Merge multiple study programs into one.
     */
    public function mergeStudyPrograms(MergeStudyProgramsRequest $request)
    {
        $this->authorize('create', StudyProgram::class);

        $targetId = $request->validated()['target_study_program_id'];
        $sourceIds = $request->validated()['source_study_program_ids'];

        DB::transaction(function () use ($targetId, $sourceIds) {
            $targetStudyProgram = StudyProgram::findOrFail($targetId);
            $sourceStudyPrograms = StudyProgram::whereIn('id', $sourceIds)->get();

            foreach ($sourceStudyPrograms as $sourceStudyProgram) {
                // Transfer all dutiables from source to target
                \App\Models\Pivots\Dutiable::where('study_program_id', $sourceStudyProgram->id)
                    ->update(['study_program_id' => $targetId]);

                // Delete the source study program
                $sourceStudyProgram->delete();
            }
        });

        return redirect()->route('studyPrograms.index')
            ->with('success', 'Study programs merged successfully.');
    }
}
