<?php

use App\Models\LecturerReview;
use App\Models\Role;
use App\Models\StudySet;
use App\Models\StudySetCourse;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'studySets.read.padalinys',
        'studySets.create.padalinys',
        'studySets.update.padalinys',
        'studySets.delete.padalinys',
    ]);

    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->studySet = StudySet::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Testinis komplektas', 'en' => 'Test Set'],
        'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        'order' => 1,
        'is_visible' => true,
    ]);
});

describe('unauthorized access', function () {
    test('cannot access index', function () {
        asUser($this->user)
            ->get(route('studySets.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)
            ->get(route('studySets.create'))
            ->assertStatus(403);
    });

    test('cannot store study set', function () {
        asUser($this->user)
            ->post(route('studySets.store'), [
                'name' => ['lt' => 'Naujas', 'en' => 'New'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403);
    });

    test('cannot access edit page', function () {
        asUser($this->user)
            ->get(route('studySets.edit', $this->studySet))
            ->assertStatus(403);
    });

    test('cannot update study set', function () {
        asUser($this->user)
            ->patch(route('studySets.update', $this->studySet), [
                'name' => ['lt' => 'Pakeistas', 'en' => 'Changed'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403);
    });

    test('cannot delete study set', function () {
        asUser($this->user)
            ->delete(route('studySets.destroy', $this->studySet))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access index', function () {
        asUser($this->admin)
            ->get(route('studySets.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/IndexStudySet')
                ->has('studySets')
                ->has('studySets.data')
                ->has('filters')
                ->has('sorting')
            );
    });

    test('can access create page', function () {
        asUser($this->admin)
            ->get(route('studySets.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/CreateStudySet')
                ->has('assignableTenants')
            );
    });

    test('can store study set with courses and reviews', function () {
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

    test('cannot store study set without required fields', function () {
        asUser($this->admin)
            ->post(route('studySets.store'), [
                'name' => ['lt' => '', 'en' => ''],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt']);
    });

    test('can access edit page with loaded relations', function () {
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

    test('can update study set and sync courses', function () {
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

    test('can delete course during update', function () {
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

    test('can delete study set', function () {
        asUser($this->admin)
            ->delete(route('studySets.destroy', $this->studySet))
            ->assertStatus(302)
            ->assertSessionHas('info');

        $this->assertDatabaseMissing('study_sets', [
            'id' => $this->studySet->id,
        ]);
    });
});

describe('tenant isolation', function () {
    beforeEach(function () {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherStudySet = StudySet::factory()->for($this->otherTenant)->create();
    });

    test('index filters by tenant', function () {
        asUser($this->admin)
            ->get(route('studySets.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StudySets/IndexStudySet')
                ->has('studySets.data')
                ->where('studySets.data', function ($data) {
                    return collect($data)->every(fn ($item) => $item['tenant_id'] === $this->tenant->id);
                })
            );
    });

    test('cannot edit other tenant study set', function () {
        asUser($this->admin)
            ->get(route('studySets.edit', $this->otherStudySet))
            ->assertStatus(403);
    });

    test('cannot update other tenant study set', function () {
        asUser($this->admin)
            ->patch(route('studySets.update', $this->otherStudySet), [
                'name' => ['lt' => 'Hacked', 'en' => 'Hacked'],
                'order' => 1,
                'tenant_id' => $this->tenant->id,
            ])
            ->assertStatus(403);
    });
});
