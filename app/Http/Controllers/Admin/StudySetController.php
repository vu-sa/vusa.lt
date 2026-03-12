<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexStudySetRequest;
use App\Http\Requests\StoreStudySetRequest;
use App\Http\Requests\UpdateStudySetRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\LecturerReview;
use App\Models\StudySet;
use App\Models\StudySetCourse;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Support\Facades\DB;

class StudySetController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexStudySetRequest $request)
    {
        $this->handleAuthorization('viewAny', StudySet::class);

        $query = StudySet::query()->with('tenant:id,shortname')
            ->withCount('courses');

        $searchableColumns = ['name'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'studySets.read.padalinys',
            ]
        );

        $studySets = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/StudySets/IndexStudySet', [
            'studySets' => [
                'data' => $studySets->getCollection()->map->toFullArray(),
                'meta' => [
                    'total' => $studySets->total(),
                    'per_page' => $studySets->perPage(),
                    'current_page' => $studySets->currentPage(),
                    'last_page' => $studySets->lastPage(),
                    'from' => $studySets->firstItem(),
                    'to' => $studySets->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', StudySet::class);

        return $this->inertiaResponse('Admin/StudySets/CreateStudySet', [
            'assignableTenants' => GetTenantsForUpserts::execute('studySets.create.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudySetRequest $request)
    {
        $this->handleAuthorization('create', StudySet::class);

        DB::transaction(function () use ($request) {
            $studySet = StudySet::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'order' => $request->input('order'),
                'is_visible' => $request->input('is_visible', true),
                'tenant_id' => $request->input('tenant_id'),
            ]);

            $this->syncCourses($studySet, $request->input('courses', []));
            $this->syncReviews($studySet, $request->input('reviews', []));
        });

        return $this->redirectToIndexWithSuccess('studySets', 'Individualių studijų komplektas sėkmingai sukurtas!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudySet $studySet)
    {
        $this->handleAuthorization('update', $studySet);

        $studySet->load([
            'courses' => fn ($query) => $query->orderBy('order'),
            'courses.reviews',
        ]);

        return $this->inertiaResponse('Admin/StudySets/EditStudySet', [
            'studySet' => [
                ...$studySet->toFullArray(),
                'courses' => $studySet->courses->map(fn (StudySetCourse $course) => [
                    ...$course->toFullArray(),
                    'reviews' => $course->reviews->map->toFullArray(),
                ]),
            ],
            'assignableTenants' => GetTenantsForUpserts::execute('studySets.update.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudySetRequest $request, StudySet $studySet)
    {
        $this->handleAuthorization('update', $studySet);

        DB::transaction(function () use ($request, $studySet) {
            $studySet->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'order' => $request->input('order'),
                'is_visible' => $request->input('is_visible', true),
                'tenant_id' => $request->input('tenant_id'),
            ]);

            $this->syncCourses($studySet, $request->input('courses', []));
            $this->syncReviews($studySet, $request->input('reviews', []));
        });

        return back()->with('success', 'Individualių studijų komplektas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudySet $studySet)
    {
        $this->handleAuthorization('delete', $studySet);

        $studySet->delete();

        return $this->redirectToIndexWithInfo('studySets', 'Individualių studijų komplektas sėkmingai ištrintas!');
    }

    private function syncCourses(StudySet $studySet, array $courses): void
    {
        $existingIds = $studySet->courses()->pluck('id')->toArray();
        $submittedIds = array_filter(array_column($courses, 'id'));

        $toDelete = array_diff($existingIds, $submittedIds);
        if (! empty($toDelete)) {
            StudySetCourse::whereIn('id', $toDelete)->delete();
        }

        foreach ($courses as $courseData) {
            if (! empty($courseData['id']) && in_array($courseData['id'], $existingIds)) {
                StudySetCourse::where('id', $courseData['id'])->update([
                    'name' => $courseData['name'],
                    'order' => $courseData['order'],
                    'semester' => $courseData['semester'],
                    'credits' => $courseData['credits'],
                    'is_visible' => $courseData['is_visible'] ?? true,
                ]);
            } else {
                $studySet->courses()->create([
                    'name' => $courseData['name'],
                    'order' => $courseData['order'],
                    'semester' => $courseData['semester'],
                    'credits' => $courseData['credits'],
                    'is_visible' => $courseData['is_visible'] ?? true,
                ]);
            }
        }
    }

    private function syncReviews(StudySet $studySet, array $reviews): void
    {
        $existingIds = $studySet->reviews()->pluck('lecturer_reviews.id')->toArray();
        $submittedIds = array_filter(array_column($reviews, 'id'));

        $toDelete = array_diff($existingIds, $submittedIds);
        if (! empty($toDelete)) {
            LecturerReview::whereIn('id', $toDelete)->delete();
        }

        foreach ($reviews as $reviewData) {
            if (! empty($reviewData['id']) && in_array($reviewData['id'], $existingIds)) {
                LecturerReview::where('id', $reviewData['id'])->update([
                    'lecturer' => $reviewData['lecturer'],
                    'comment' => $reviewData['comment'],
                    'study_set_course_id' => $reviewData['study_set_course_id'],
                    'is_visible' => $reviewData['is_visible'] ?? true,
                ]);
            } else {
                LecturerReview::create([
                    'lecturer' => $reviewData['lecturer'],
                    'comment' => $reviewData['comment'],
                    'study_set_course_id' => $reviewData['study_set_course_id'],
                    'is_visible' => $reviewData['is_visible'] ?? true,
                ]);
            }
        }
    }
}
