<?php

use App\Actions\RecordMeeting;
use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Mail\NotificationDigest;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use Database\Seeders\DocsSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;

pest()->use(RefreshDatabase::class);

it('renders equal-width email actions for each institution on narrow and wide screens', function (): void {
    $first = InstitutionActivityRequest::factory()->create(['period_end' => today()]);
    $second = InstitutionActivityRequest::factory()->create(['recipient_id' => $first->recipient_id, 'period_end' => today()]);
    $page = visit('/up');
    foreach (['activity_confirmation', 'missing_meetings', 'digest'] as $campaign) {
        if ($campaign === 'missing_meetings') {
            foreach ([$first, $second] as $request) {
                $request->update(['campaign_type' => $campaign]);
                app(RecordMeeting::class)->execute($request->institution, now()->subWeek());
            }
        }
        $notification = new InstitutionActivityNotification(new Collection([$first, $second]));
        $mail = $campaign === 'digest' ? new NotificationDigest($first->recipient, ['meeting' => [$notification->toDigestItem($first->recipient)]]) : $notification->toMail($first->recipient);
        $page->page()->setContent((string) $mail->render());
        expect($page->script('Array.from(document.querySelectorAll("table")).filter(table => table.style.tableLayout === "fixed").length'))->toBe(2);
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900);
            expect($page->script('document.documentElement.scrollWidth <= innerWidth'))->toBeTrue();
            $page->screenshot(filename: "activity-email-{$campaign}-{$width}");
        }
    }
});

it('submits several meetings through the signed form and renders without overflow in either theme', function (): void {
    app(Vite::class)->useHotFile(storage_path('framework/testing/vite-hot-disabled'));
    $page = visit('/up');
    $request = InstitutionActivityRequest::factory()->create(['campaign_type' => 'missing_meetings', 'period_end' => today(), 'locale' => 'lt']);
    $page->navigate($request->answerUrl(InstitutionActivityAnswer::Met));
    $page->page()->waitForSelector('#activity-reply form', ['timeout' => 15000]);
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            expect($page->script('document.documentElement.scrollWidth <= innerWidth'))->toBeTrue();
            $page->screenshot(filename: "activity-reply-{$width}-{$theme}");
        }
    }
    $page->fill('#date-0', today()->subWeek()->toDateString());
    $page->fill('#time-0', '09:30');
    $page->click('button:has-text("Pridėti posėdį")');
    $page->fill('#date-1', today()->subDay()->toDateString());
    $page->select('#type-1', 'email');
    $page->assertMissing('#time-1');
    $page->script('document.querySelector("button[aria-controls=answer-complete]").focus()');
    $page->page()->locator('button[aria-controls=answer-complete]')->press('Enter');
    $page->assertPresent('#answer-complete');
    $page->click('button[aria-controls=answer-met]');
    $page->assertNoJavaScriptErrors();
    $page->click('button[type=submit]');
    $page->assertSee(__('activity_requests.done.met'))->assertNoJavaScriptErrors();
    expect($request->fresh()->meetings)->toHaveCount(2)->and(Meeting::query()->where('type', 'email')->sole()->start_time->format('H:i'))->toBe('23:59');
});

it('opens request history from an institution record against the built bundle', function (): void {
    $user = makeAdminUser(Tenant::query()->first());
    $request = InstitutionActivityRequest::factory()->create(['requested_by_id' => $user->id, 'period_end' => today()]);
    $page = loginAsAdmin($user);
    $page->navigate(route('institutions.show', $request->institution));
    waitForInertiaRender($page, '[data-slot=admin-shell]');
    $page->click('[role=tab]:has-text("Užklausos atstovams")');
    $page->assertSee($request->recipient->name)->assertNoJavaScriptErrors();
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            expect($page->script('document.documentElement.scrollWidth <= innerWidth'))->toBeTrue();
            $page->page()->locator('[data-slot=activity-request-history]')->scrollIntoViewIfNeeded();
            $page->screenshot(filename: "activity-history-{$width}-{$theme}");
        }
    }
});

it('reviews incomplete records for the sending representative without overflow in the action window', function (): void {
    $user = makeAdminUser(Tenant::query()->first());
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $duty = Duty::factory()->for($institution)->create();
    $type = DutyType::query()->firstOrCreate(['slug' => 'studentu-atstovai'], ['name' => ['lt' => 'Studentų atstovai', 'en' => 'Student representatives']]);
    $duty->types()->attach($type);
    $user->duties()->attach($duty, ['start_date' => today()->subMonth()]);
    app(RecordMeeting::class)->execute($institution, now()->subWeek());
    $page = loginAsAdmin($user);
    $page->navigate(route('institutions.show', $institution));
    waitForInertiaRender($page, '[data-slot=admin-shell]');
    $page->click('[data-testid=record-overflow-trigger]');
    $page->click('[role=menuitem]:has-text("Paklausti, ar vyko posėdžiai")');
    $page->click('[data-slot=action-choice-button]:has-text("Papildyk posėdžių įrašus")');
    pickPreselectedInstitutions($page);
    $page->page()->waitForSelector('[data-slot=activity-request-preview]', ['timeout' => 15000]);
    $page->assertPresent('[data-slot=activity-request-preview]')->assertNoJavaScriptErrors();
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);
        $page->page()->waitForSelector('[data-slot=activity-request-preview]', ['timeout' => 15000]);
        $page->assertPresent('[data-slot=activity-request-preview]');
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            expect($page->script('document.documentElement.scrollWidth <= innerWidth'))->toBeTrue();
            $page->screenshot(filename: "activity-review-{$width}-{$theme}");
        }
    }
});

it('captures documentation reference frames for activity requests', function (): void {
    $this->seed(DocsSeeder::class);

    $coordinator = User::query()->firstWhere('email', DocsSeeder::COORDINATOR_EMAIL);
    $representative = User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL);
    $committee = Institution::query()->where('name->lt', 'Chemijos studijų programos komitetas')->firstOrFail();
    $request = InstitutionActivityRequest::query()->where('institution_id', $committee->id)->firstOrFail();
    $request->delete();

    // 1. Action window review screen
    $page = loginAsAdmin($coordinator);
    $page->navigate(route('institutions.show', $committee));
    waitForInertiaRender($page, '[data-slot=admin-shell]');
    $page->click('[data-testid=record-overflow-trigger]');
    $page->click('[role=menuitem]:has-text("Paklausti, ar vyko posėdžiai")');
    $page->click('[data-slot=action-choice-button]:has-text("Ar vyko posėdis?")');
    pickPreselectedInstitutions($page);
    $page->page()->waitForSelector('[data-slot=activity-request-preview]', ['timeout' => 15000]);
    $page->fill('#activity-request-note', 'Ar per pastarąjį mėnesį vyko Chemijos SPK posėdis? Laukiame informacijos apie priimtus sprendimus.');
    docsScreenshot($page, 'activity-request-review', selector: '[role="dialog"]');
    $page->assertNoJavaScriptErrors();

    // Recreate the request for the email and reply frames
    $request = InstitutionActivityRequest::factory()->create([
        'institution_id' => $committee->id,
        'recipient_id' => $representative->id,
        'requested_by_id' => $coordinator->id,
        'campaign_type' => InstitutionActivityCampaign::ActivityConfirmation,
        'period_start' => now()->subDays(30),
        'period_end' => today(),
        'note' => 'Ar per pastarąjį mėnesį vyko Chemijos SPK posėdis? Laukiame informacijos apie priimtus sprendimus.',
        'locale' => 'lt',
    ]);

    // 2. Request history on the institution record, with an earlier automatic request already answered
    $meeting = app(RecordMeeting::class)->execute($committee, now()->subMonths(2)->subWeek());
    InstitutionActivityRequest::factory()->create([
        'institution_id' => $committee->id,
        'recipient_id' => $representative->id,
        'requested_by_id' => null,
        'campaign_type' => InstitutionActivityCampaign::ActivityConfirmation,
        'period_start' => now()->subMonths(3),
        'period_end' => now()->subMonths(2),
        'answer' => InstitutionActivityAnswer::Met,
        'answered_at' => now()->subMonths(2)->addDay(),
        'meeting_id' => $meeting->id,
        'note' => null,
        'created_at' => now()->subMonths(2),
    ]);
    $page->navigate(route('institutions.show', $committee));
    waitForInertiaRender($page, '[data-slot=admin-shell]');
    $page->click('[role=tab]:has-text("Užklausos atstovams")');
    waitForInertiaRender($page, '[data-slot=activity-request-history]');
    $page->resize(1180, 900);
    docsScreenshot($page, 'activity-request-history', selector: '[data-slot=activity-request-history]');
    $page->resize(1440, 1100);
    $page->script('document.querySelector("[data-slot=admin-scroll-area]")?.scrollTo(0, 0)');
    docsScreenshot($page, 'v3-request-history', highlights: ['[data-slot=activity-request-history]']);

    // 3. Email notification
    $notification = new InstitutionActivityNotification(new Collection([$request]));
    $mail = $notification->toMail($representative);
    $page->resize(640, 750);
    // setContent over the admin shell never reaches "load"; start from a bare page.
    $page->navigate('/up');
    $page->page()->setContent((string) $mail->render());
    docsScreenshot($page, 'activity-request-email');

    // 4. Public non-login answer page
    app(Vite::class)->useHotFile(storage_path('framework/testing/vite-hot-disabled'));
    $page->navigate($request->answerUrl(InstitutionActivityAnswer::Met));
    $page->page()->waitForSelector('#activity-reply form', ['timeout' => 15000]);
    $page->resize(820, 900);
    docsScreenshot($page, 'activity-request-reply', selector: 'main');
    $page->assertNoJavaScriptErrors();
});

/** "Pagal institucijas", then continue with the institutions the window was opened on. */
function pickPreselectedInstitutions($page): void
{
    $page->click('[data-slot=action-choice-button]:has-text("Pagal institucijas")');
    waitForInertiaRender($page, '[data-slot=action-window-primary]:not([disabled])');
    $page->click('[data-slot=action-window-primary]');
}
