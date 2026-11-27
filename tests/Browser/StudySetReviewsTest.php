<?php

use App\Models\LecturerReview;
use App\Models\Role;
use App\Models\StudySet;
use App\Models\StudySetCourse;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findByName('Komunikacijos koordinatorius')->givePermissionTo([
        'studySets.read.padalinys',
        'studySets.create.padalinys',
        'studySets.update.padalinys',
    ]);
});

test('the review form preserves an existing review when saving an edit', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $studySet = StudySet::factory()->for($tenant)->create();
    $course = StudySetCourse::factory()->for($studySet)->create();
    $review = LecturerReview::factory()->create(['study_set_course_id' => $course->id]);

    $page = loginAsAdmin($user, "/mano/studySets/{$studySet->id}/edit");
    waitForInertiaRender($page, '#review-lecturer-0');
    $page->fill('#review-lecturer-0', 'Pakeistas dėstytojas');

    // Not the success toast: it auto-dismisses after ~4s and was already gone on a slow CI run.
    waitForInertiaRender($page, '[data-testid=form-page-bar] [data-save-state=dirty]');
    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[data-testid=form-page-bar] [data-save-state=saved]');
    $page->assertNoJavaScriptErrors();

    expect($review->fresh()->getTranslation('lecturer', 'lt'))->toBe('Pakeistas dėstytojas')
        ->and($course->reviews()->count())->toBe(1);
});
