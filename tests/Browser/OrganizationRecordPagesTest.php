<?php

use App\Models\Duty;
use App\Models\Form;
use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Seated members, ended terms and real registrations: empty records would hide layout bugs.
    $this->seed(DocsSeeder::class);

    $this->coordinator = User::query()->firstWhere('email', DocsSeeder::COMMUNICATION_COORDINATOR_EMAIL);
});

/** The record fits the viewport at a phone and a desktop width. */
function expectOrganizationRecordFits($page): void
{
    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
            ->and($page->script("document.querySelector('[data-slot=record-title]').getBoundingClientRect().right <= window.innerWidth"))->toBeTrue();
    }
}

it('opens a duty with its current and past members', function (): void {
    $duty = Duty::query()->where('name->lt', 'Parlamento narys (-ė)')->firstOrFail();

    $page = loginAsAdmin($this->coordinator);
    $page->navigate("/mano/duties/{$duty->id}");
    waitForInertiaRender($page, '[data-testid="member-term-edit"]');

    expectOrganizationRecordFits($page);

    $page->resize(1440, 1000);
    docsScreenshot($page, 'duty-record');

    $page->assertNoJavaScriptErrors();
});

it('opens a member with the duties they hold and held', function (): void {
    $member = User::query()->where('name', 'Mantas Jonaitis')->firstOrFail();

    $page = loginAsAdmin($this->coordinator);
    $page->navigate("/mano/users/{$member->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    $page->assertSee('Mantas Jonaitis');
    expectOrganizationRecordFits($page);

    $page->resize(1440, 1000);
    docsScreenshot($page, 'user-record');

    $page->assertNoJavaScriptErrors();
});

it('opens a form with its registrations', function (): void {
    $form = Form::query()->where('name->lt', DocsSeeder::REGISTRATION_FORM)->firstOrFail();

    $page = loginAsAdmin($this->coordinator);
    $page->navigate("/mano/forms/{$form->id}");
    waitForInertiaRender($page, '[data-slot=record-title]');

    $page->assertSee('Karolina Jankauskaitė');
    expectOrganizationRecordFits($page);

    $page->resize(1440, 1000);
    docsScreenshot($page, 'form-record');

    $page->assertNoJavaScriptErrors();
});

it('shows a representative their duties, roles and reachable sections', function (): void {
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano/profile/roles');
    waitForInertiaRender($page, '[data-testid="current-duties"]');

    foreach ([390, 1440] as $width) {
        $page->resize($width, 900);

        expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
    }

    $page->resize(1440, 1000);
    docsScreenshot($page, 'my-roles');

    $page->assertNoJavaScriptErrors();
});
