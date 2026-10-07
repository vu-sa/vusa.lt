<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexStudySetRequest;
use App\Http\Requests\StoreStudySetRequest;
use App\Http\Requests\UpdateStudySetRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\StudySet;
use App\Models\StudySetCourse;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudySetController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexStudySetRequest $request)
    {
        $this->handleAuthorization('viewAny', StudySet::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        $query = $this->tableService->applyPermissionFiltering(
            StudySet::query()->with('tenant:id,shortname')->withCount('courses')->orderBy('order'),
            'tenant',
            'studySets.read.padalinys',
            $this->authorizer,
        );

        return $this->inertiaResponse('Admin/StudySets/IndexStudySet', [
            'studySets' => ($request->getShowDeleted() ? $query->onlyTrashed() : $query)->get()->map->toFullArray()->values(),
            'deletedCount' => $this->scopedTrashedCount(StudySet::query(), 'tenant', 'studySets.read.padalinys'),
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

        DB::transaction(function () use ($request): void {
            $studySet = StudySet::create([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'order' => $request->validated('order'),
                'is_visible' => $request->validated('is_visible', true),
                'tenant_id' => $request->validated('tenant_id'),
            ]);

            $this->syncCourses($studySet, ($request->validated('courses') ?? []));
            $this->syncReviews($studySet, ($request->validated('reviews') ?? []));
        });

        return $this->redirectToIndexWithSuccess('studySets', $this->entityMessage('created', 'studySet'));
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
                'reviews' => $studySet->courses->flatMap(fn (StudySetCourse $course) => $course->reviews->map->toFullArray())->values(),
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

        DB::transaction(function () use ($request, $studySet): void {
            $studySet->update([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'order' => $request->validated('order'),
                'is_visible' => $request->validated('is_visible', true),
                'tenant_id' => $request->validated('tenant_id'),
            ]);

            $this->syncCourses($studySet, ($request->validated('courses') ?? []));
            $this->syncReviews($studySet, ($request->validated('reviews') ?? []));
        });

        return back()->with('success', $this->entityMessage('updated', 'studySet'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudySet $studySet)
    {
        $this->handleAuthorization('delete', $studySet);

        $studySet->delete();

        return $this->redirectToIndexWithInfo('studySets', $this->entityMessage('deleted', 'studySet'));
    }

    private function syncCourses(StudySet $studySet, array $courses): void
    {
        $submittedIds = array_filter(array_column($courses, 'id'));
        $studySet->courses()->whereNotIn('id', $submittedIds)->delete();

        foreach ($courses as $index => $courseData) {
            $attributes = Arr::only($courseData, ['name', 'order', 'semester', 'credits', 'is_visible']);
            $attributes['is_visible'] ??= true;

            if (! empty($courseData['id'])) {
                $course = $studySet->courses()->find($courseData['id']);
                if ($course === null) {
                    throw ValidationException::withMessages(["courses.{$index}.id" => __('validation.exists', ['attribute' => 'id'])]);
                }
                $course->update($attributes);
            } else {
                $studySet->courses()->create($attributes);
            }
        }
    }

    private function syncReviews(StudySet $studySet, array $reviews): void
    {
        $courses = $studySet->courses()->get()->keyBy('id');
        foreach ($reviews as $index => $reviewData) {
            if (! $courses->has($reviewData['study_set_course_id'])) {
                throw ValidationException::withMessages([
                    "reviews.{$index}.study_set_course_id" => __('validation.exists', ['attribute' => 'study_set_course_id']),
                ]);
            }
        }

        $submittedIds = array_filter(array_column($reviews, 'id'));
        $studySet->reviews()->whereNotIn('lecturer_reviews.id', $submittedIds)->get()->each->delete();

        foreach ($reviews as $index => $reviewData) {
            $attributes = Arr::only($reviewData, ['lecturer', 'comment', 'is_visible']);
            $attributes['is_visible'] ??= true;
            $course = $courses->get($reviewData['study_set_course_id']);

            if (! empty($reviewData['id'])) {
                $review = $studySet->reviews()->find($reviewData['id']);
                if ($review === null) {
                    throw ValidationException::withMessages(["reviews.{$index}.id" => __('validation.exists', ['attribute' => 'id'])]);
                }
                $review->update([...$attributes, 'study_set_course_id' => $course->id]);
            } else {
                $course->reviews()->create($attributes);
            }
        }
    }

    public function restore(StudySet $studySet): RedirectResponse
    {
        return $this->restoreModel($studySet);
    }

    public function forceDelete(StudySet $studySet): RedirectResponse
    {
        return $this->forceDeleteModel($studySet);
    }
}
