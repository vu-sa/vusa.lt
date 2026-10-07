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

    $page = loginAsAdmin($user);
    $page->navigate("/mano/studySets/{$studySet->id}/edit");
    waitForInertiaRender($page, '#review-lecturer-0');
    $page->fill('#review-lecturer-0', 'Pakeistas dėstytojas');

    foreach ([390, 1440] as $width) {
        $page->resize($width, 1100);
        $page->script('document.querySelector("#review-lecturer-0").scrollIntoView({block: "center"})');
        $page->screenshot(filename: "security-studyset-review-{$width}");
        $page->script('document.documentElement.classList.add("dark")');
        $page->screenshot(filename: "security-studyset-review-dark-{$width}");
        $page->script('document.documentElement.classList.remove("dark")');
    }

    $page->click('[data-testid=form-page-save]')
        ->assertSee(__('messages.updated.m', ['model' => trans_choice('entities.studySet.model', 1)]))
        ->assertNoJavaScriptErrors();

    expect($review->fresh()->getTranslation('lecturer', 'lt'))->toBe('Pakeistas dėstytojas');
    expect($course->reviews()->count())->toBe(1);
});

test('a new study set explains when reviews can be added', function (): void {
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', Tenant::query()->firstOrFail());
    $page = loginAsAdmin($user);
    $page->navigate('/mano/studySets/create');
    waitForInertiaRender($page, '[data-slot=form-page]');
    $page->assertSee('Pirmiausia išsaugok dalykus')
        ->assertDontSee('Pridėti atsiliepimą')
        ->assertNoJavaScriptErrors();
});
