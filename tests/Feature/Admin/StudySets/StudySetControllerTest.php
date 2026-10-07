<?php

use App\Models\LecturerReview;
use App\Models\Role;
use App\Models\StudySet;
use App\Models\StudySetCourse;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'studySets.read.padalinys',
        'studySets.create.padalinys',
        'studySets.update.padalinys',
        'studySets.delete.padalinys',
    ]);

    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

    $this->studySet = StudySet::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Testinis komplektas', 'en' => 'Test Set'],
        'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        'order' => 1,
        'is_visible' => true,
    ]);
});

describe('unauthorized access', function (): void {
    test('cannot access index', function (): void {
        asUser($this->user)
            ->get(route('studySets.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)
            ->get(route('studySets.create'))
            ->assertStatus(403);
    });

    test('cannot store study set', function (): void {
        asUser($this->user)
            ->post(route('studySets.store'), [
                'name' => ['lt' => 'Naujas', 'en' => 'New'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403);
    });

    test('cannot access edit page', function (): void {
        asUser($this->user)
            ->get(route('studySets.edit', $this->studySet))
            ->assertStatus(403);
    });

    test('cannot update study set', function (): void {
        asUser($this->user)
            ->patch(route('studySets.update', $this->studySet), [
                'name' => ['lt' => 'Pakeistas', 'en' => 'Changed'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403);
    });

    test('cannot delete study set', function (): void {
        asUser($this->user)
            ->delete(route('studySets.destroy', $this->studySet))
            ->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index', function (): void {
        asUser($this->admin)
            ->get(route('studySets.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/IndexStudySet')
                ->has('studySets')
                ->has('deletedCount')
            );
    });

    test('can access create page', function (): void {
        asUser($this->admin)
            ->get(route('studySets.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/CreateStudySet')
                ->has('assignableTenants')
            );
    });

    test('can store study set with courses and reviews', function (): void {
        $response = asUser($this->admin)
            ->post(route('studySets.store'), [
                'name' => ['lt' => 'Naujas komplektas', 'en' => 'New Set'],
                'description' => ['lt' => 'Naujas aprašymas', 'en' => 'New description'],
                'order' => 2,
                'is_visible' => true,
                'tenant_id' => $this->tenant->id,
                'courses' => [
                    [
                        'name' => ['lt' => 'Kursas 1', 'en' => 'Course 1'],
                        'semester' => 'autumn',
                        'credits' => 6,
                        'order' => 1,
                        'is_visible' => true,
                    ],
                ],
                'reviews' => [],
            ]);

        $response->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('study_sets', [
            'name->lt' => 'Naujas komplektas',
            'name->en' => 'New Set',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertDatabaseHas('study_set_courses', [
            'name->lt' => 'Kursas 1',
            'credits' => 6,
            'semester' => 'autumn',
        ]);
    });

    test('cannot store study set without required fields', function (): void {
        asUser($this->admin)
            ->post(route('studySets.store'), [
                'name' => ['lt' => '', 'en' => ''],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt']);
    });

    test('can access edit page with loaded relations', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        LecturerReview::factory()->create(['study_set_course_id' => $course->id]);

        asUser($this->admin)
            ->get(route('studySets.edit', $this->studySet))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/EditStudySet')
                ->has('studySet')
                ->where('studySet.id', $this->studySet->id)
                ->has('studySet.courses')
                ->has('assignableTenants')
            );
    });

    test('can update study set and sync courses', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create([
            'name' => ['lt' => 'Senas kursas', 'en' => 'Old Course'],
            'semester' => 'autumn',
            'credits' => 3,
            'order' => 1,
        ]);

        asUser($this->admin)
            ->patch(route('studySets.update', $this->studySet), [
                'name' => ['lt' => 'Atnaujintas komplektas', 'en' => 'Updated Set'],
                'description' => ['lt' => 'Atnaujintas aprašymas', 'en' => 'Updated description'],
                'order' => 5,
                'is_visible' => false,
                'tenant_id' => $this->tenant->id,
                'courses' => [
                    [
                        'id' => $course->id,
                        'name' => ['lt' => 'Atnaujintas kursas', 'en' => 'Updated Course'],
                        'semester' => 'spring',
                        'credits' => 4,
                        'order' => 2,
                        'is_visible' => true,
                    ],
                    [
                        'name' => ['lt' => 'Naujas kursas', 'en' => 'New Course'],
                        'semester' => 'autumn',
                        'credits' => 5,
                        'order' => 3,
                        'is_visible' => true,
                    ],
                ],
                'reviews' => [],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('study_sets', [
            'id' => $this->studySet->id,
            'name->lt' => 'Atnaujintas komplektas',
            'order' => 5,
        ]);

        $this->assertDatabaseHas('study_set_courses', [
            'id' => $course->id,
            'name->lt' => 'Atnaujintas kursas',
            'semester' => 'spring',
            'credits' => 4,
        ]);

        $this->assertDatabaseHas('study_set_courses', [
            'name->lt' => 'Naujas kursas',
            'credits' => 5,
        ]);
    });

    test('can delete course during update', function (): void {
        $course1 = StudySetCourse::factory()->for($this->studySet)->create();
        $course2 = StudySetCourse::factory()->for($this->studySet)->create();

        asUser($this->admin)
            ->patch(route('studySets.update', $this->studySet), [
                'name' => ['lt' => 'Komplektas', 'en' => 'Set'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
                'courses' => [
                    [
                        'id' => $course1->id,
                        'name' => ['lt' => 'Paliktas kursas', 'en' => 'Kept Course'],
                        'semester' => 'autumn',
                        'credits' => 3,
                        'order' => 1,
                    ],
                ],
                'reviews' => [],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('study_set_courses', ['id' => $course1->id]);
        $this->assertDatabaseMissing('study_set_courses', ['id' => $course2->id]);
    });

    test('can delete study set', function (): void {
        asUser($this->admin)
            ->delete(route('studySets.destroy', $this->studySet))
            ->assertStatus(302)
            ->assertSessionHas('info');

        $this->assertSoftDeleted('study_sets', [
            'id' => $this->studySet->id,
        ]);
    });
});

describe('tenant isolation', function (): void {
    beforeEach(function (): void {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherStudySet = StudySet::factory()->for($this->otherTenant)->create();
    });

    test('index filters by tenant', function (): void {
        asUser($this->admin)
            ->get(route('studySets.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/IndexStudySet')
                ->has('studySets')
                ->where('studySets', fn ($data) => collect($data)->every(fn ($item) => $item['tenant_id'] === $this->tenant->id))
            );
    });

    test('cannot edit other tenant study set', function (): void {
        asUser($this->admin)
            ->get(route('studySets.edit', $this->otherStudySet))
            ->assertStatus(403);
    });

    test('cannot update other tenant study set', function (): void {
        asUser($this->admin)
            ->patch(route('studySets.update', $this->otherStudySet), [
                'name' => ['lt' => 'Hacked', 'en' => 'Hacked'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403);
    });
});

function studySetReviewPayload(StudySet $studySet, array $courses, array $reviews): array
{
    return [
        'name' => ['lt' => 'Pakeistas rinkinys', 'en' => 'Updated set'],
        'order' => 2, 'tenant_id' => $studySet->tenant_id,
        'courses' => array_map(fn (StudySetCourse $course): array => $course->toFullArray(), $courses),
        'reviews' => $reviews,
    ];
}

function studySetReviewData(StudySetCourse $course, array $changes = []): array
{
    return array_replace([
        'lecturer' => ['lt' => 'Dėstytojas', 'en' => 'Lecturer'],
        'comment' => ['lt' => 'Atsiliepimas', 'en' => 'Review'],
        'study_set_course_id' => $course->id, 'is_visible' => true,
    ], $changes);
}

describe('review ownership', function (): void {
    test('foreign courses cannot receive new or reassigned reviews', function (bool $otherTenant, bool $existingReview): void {
        $ownCourse = StudySetCourse::factory()->for($this->studySet)->create();
        $tenant = $otherTenant ? Tenant::where('id', '!=', $this->tenant->id)->firstOrFail() : $this->tenant;
        $otherSet = StudySet::factory()->for($tenant)->create();
        $otherCourse = StudySetCourse::factory()->for($otherSet)->create();
        $review = $existingReview ? LecturerReview::factory()->create(['study_set_course_id' => $ownCourse->id]) : null;
        $original = $this->studySet->fresh()->getAttributes();

        asUser($this->admin)->patch(route('studySets.update', $this->studySet), studySetReviewPayload(
            $this->studySet, [$ownCourse], [studySetReviewData($otherCourse, $review ? ['id' => $review->id] : [])],
        ))->assertSessionHasErrors('reviews.0.study_set_course_id');

        expect($this->studySet->fresh()->getAttributes())->toBe($original);
        expect($otherCourse->reviews()->count())->toBe(0);
        if ($review) {
            expect($review->fresh()->study_set_course_id)->toBe($ownCourse->id);
        }
    })->with([false, true])->with([false, true]);

    test('store cannot inject reviews into an existing course', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        asUser($this->admin)->post(route('studySets.store'), studySetReviewPayload($this->studySet, [], [studySetReviewData($course)]))
            ->assertSessionHasErrors('reviews');
        expect(StudySet::count())->toBe(1);
        expect($course->reviews()->count())->toBe(0);
    });

    test('reviews cannot reference a course removed in the same submission', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        $review = LecturerReview::factory()->create(['study_set_course_id' => $course->id]);
        asUser($this->admin)->patch(route('studySets.update', $this->studySet), studySetReviewPayload(
            $this->studySet, [], [studySetReviewData($course, ['id' => $review->id])],
        ))->assertSessionHasErrors('reviews.0.study_set_course_id');
        expect($course->fresh())->not->toBeNull();
        expect($review->fresh())->not->toBeNull();
    });

    test('foreign child ids and duplicates are rejected', function (string $failure): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        $otherCourse = StudySetCourse::factory()->create();
        $foreignReview = LecturerReview::factory()->create(['study_set_course_id' => $otherCourse->id]);
        $ownReview = LecturerReview::factory()->create(['study_set_course_id' => $course->id]);
        $courses = [$course];
        $reviews = [studySetReviewData($course, ['id' => $ownReview->id])];
        $field = 'reviews.0.id';
        if ($failure === 'foreign review') {
            $reviews[0]['id'] = $foreignReview->id;
        } elseif ($failure === 'foreign course') {
            $courses[] = $otherCourse;
            $field = 'courses.1.id';
        } elseif ($failure === 'duplicate review') {
            $reviews[] = $reviews[0];
        } else {
            $courses[] = $course;
            $field = 'courses.0.id';
        }

        asUser($this->admin)->patch(route('studySets.update', $this->studySet), studySetReviewPayload($this->studySet, $courses, $reviews))
            ->assertSessionHasErrors($field);
        expect($ownReview->fresh())->not->toBeNull();
        expect($foreignReview->fresh()->study_set_course_id)->toBe($otherCourse->id);
    })->with(['foreign review', 'foreign course', 'duplicate review', 'duplicate course']);

    test('a malformed course id returns validation errors', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        $payload = studySetReviewPayload($this->studySet, [$course], []);
        $payload['courses'][0]['id'] = [$course->id];

        asUser($this->admin)->patch(route('studySets.update', $this->studySet), $payload)
            ->assertSessionHasErrors('courses.0.id');
        expect($course->fresh())->not->toBeNull();
    });

    test('same-set reviews can be created updated and reassigned', function (string $operation): void {
        $first = StudySetCourse::factory()->for($this->studySet)->create();
        $second = StudySetCourse::factory()->for($this->studySet)->create();
        $review = $operation !== 'create' ? LecturerReview::factory()->create(['study_set_course_id' => $first->id]) : null;
        $target = $operation === 'reassign' ? $second : $first;
        $data = studySetReviewData($target, $review ? ['id' => $review->id] : []);
        $data['ignored'] = 'unvalidated';

        asUser($this->admin)->patch(route('studySets.update', $this->studySet), studySetReviewPayload($this->studySet, [$first, $second], [$data]))
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $saved = $target->reviews()->sole();
        expect($saved->getTranslations('comment'))->toBe(['lt' => 'Atsiliepimas', 'en' => 'Review']);
        if ($review) {
            expect($saved->id)->toBe($review->id);
        }
    })->with(['create', 'update', 'reassign']);

    test('existing reviews are included in the edit form payload', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        $review = LecturerReview::factory()->create(['study_set_course_id' => $course->id]);
        asUser($this->admin)->get(route('studySets.edit', $this->studySet))->assertInertia(fn (Assert $page) => $page
            ->has('studySet.reviews', 1)->where('studySet.reviews.0.id', $review->id));
    });

    test('omitting a review deletes it without deleting its course', function (): void {
        $course = StudySetCourse::factory()->for($this->studySet)->create();
        $review = LecturerReview::factory()->create(['study_set_course_id' => $course->id]);
        asUser($this->admin)->patch(route('studySets.update', $this->studySet), studySetReviewPayload($this->studySet, [$course], []))
            ->assertSessionHasNoErrors();
        expect($review->fresh())->toBeNull();
        expect($course->fresh())->not->toBeNull();
    });
});

test('a review target lost during course synchronization rolls back the whole update', function (): void {
    $course = StudySetCourse::factory()->for($this->studySet)->create();
    $review = LecturerReview::factory()->create(['study_set_course_id' => $course->id]);
    $original = $this->studySet->fresh()->getAttributes();
    $payload = studySetReviewPayload($this->studySet, [$course], [studySetReviewData($course, ['id' => $review->id])]);
    $payload['courses'][0]['name'] = ['lt' => 'Pakeistas dalykas', 'en' => 'Changed course'];
    Event::listen('eloquent.updated: '.StudySetCourse::class, function (StudySetCourse $updated): void {
        $updated->delete();
    });

    asUser($this->admin)->patch(route('studySets.update', $this->studySet), $payload)
        ->assertSessionHasErrors('reviews.0.study_set_course_id');
    expect($this->studySet->fresh()->getAttributes())->toBe($original);
    expect($course->fresh())->not->toBeNull();
    expect($review->fresh())->not->toBeNull();
});
