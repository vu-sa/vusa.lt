<?php

use App\Contracts\GuardsForceDelete;
use App\Models\Category;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Registration;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The trash view offers permanent deletion for every soft-deletable model, but most of
 * them are referenced by restricting foreign keys (the action always failed) or by
 * cascading ones (the action succeeded while destroying records that must outlive it —
 * submitted registrations, reported problems, service history).
 *
 * Models that own their dependents cascade instead; those are covered at the bottom.
 */
describe('blocked', function () {
    test('an institution with meetings cannot be permanently deleted', function () {
        $institution = Institution::factory()->create();
        $institution->meetings()->attach(Meeting::factory()->create());

        expect($institution->forceDeleteBlockedReason())->toBeString()
            ->and($institution->forceDeleteBlockedReason())->toContain('1');
    });

    test('a form with submitted registrations cannot be permanently deleted', function () {
        $form = Form::factory()->create();
        Registration::factory()->for($form)->create();

        expect($form->forceDeleteBlockedReason())->toBeString();
    });

    test('a category still used by news cannot be permanently deleted', function () {
        $category = Category::factory()->create();
        News::factory()->create(['category_id' => $category->id]);

        expect($category->forceDeleteBlockedReason())->toBeString();
    });

    test('a resource with reservation history cannot be permanently deleted', function () {
        $resource = Resource::factory()->create();
        $resource->reservations()->attach(Reservation::factory()->create(), [
            'quantity' => 1,
            'state' => 'created',
        ]);

        expect($resource->forceDeleteBlockedReason())->toBeString();
    });

    test('an unreferenced record reports no blocker', function () {
        expect(Category::factory()->create()->forceDeleteBlockedReason())->toBeNull()
            ->and(Form::factory()->create()->forceDeleteBlockedReason())->toBeNull()
            ->and(StudyProgram::factory()->create()->forceDeleteBlockedReason())->toBeNull()
            ->and(Training::factory()->create()->forceDeleteBlockedReason())->toBeNull();
    });

    test('the reason names the referencing records rather than being generic', function () {
        $category = Category::factory()->create();
        News::factory()->count(2)->create(['category_id' => $category->id]);

        expect($category->forceDeleteBlockedReason())
            ->toContain('2')
            ->not->toBe(__('trash.blocked.has_related_records'));
    });
});

describe('through the controller', function () {
    test('a blocked record stays trashed and the user is told why', function () {
        $tenant = Tenant::query()->first();
        $admin = makeAdminUser($tenant);

        $category = Category::factory()->create();
        News::factory()->create(['category_id' => $category->id]);
        $category->delete();

        asUser($admin)
            ->delete(route('categories.forceDelete', $category->id))
            ->assertRedirect()
            ->assertSessionHas('error', $category->forceDeleteBlockedReason());

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    });

    test('an unblocked record is permanently deleted', function () {
        $tenant = Tenant::query()->first();
        $admin = makeAdminUser($tenant);

        $category = Category::factory()->create();
        $category->delete();

        asUser($admin)
            ->delete(route('categories.forceDelete', $category->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });
});

describe('cascading models', function () {
    test('a reservation detaches its resources on permanent deletion only', function () {
        $reservation = Reservation::factory()->create();
        $reservation->resources()->attach(Resource::factory()->create(), [
            'quantity' => 1,
            'state' => 'created',
        ]);

        $reservation->delete();
        $this->assertDatabaseHas('reservation_resource', ['reservation_id' => $reservation->id]);

        $reservation->forceDelete();
        $this->assertDatabaseMissing('reservation_resource', ['reservation_id' => $reservation->id]);
    });
});

test('every guarded model exposes the reason as an appendable attribute', function () {
    // The admin index serializes `force_delete_blocked_reason` so the table can disable
    // the action before it is clicked.
    $models = [
        Category::factory()->create(),
        Form::factory()->create(),
        StudyProgram::factory()->create(),
        Training::factory()->create(),
        Institution::factory()->create(),
    ];

    foreach ($models as $model) {
        expect($model)->toBeInstanceOf(GuardsForceDelete::class)
            ->and($model->append('force_delete_blocked_reason')->toArray())
            ->toHaveKey('force_delete_blocked_reason');
    }
});
