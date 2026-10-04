<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('mounts duty headings and personalized contact names without overflow or JavaScript errors', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create([
        'name' => ['lt' => 'VU SA kontaktai', 'en' => 'VU SR contacts'],
        'description' => ['lt' => '', 'en' => ''], 'image_url' => null,
    ]);
    $duty = Duty::factory()->for($institution)->create([
        'name' => ['lt' => 'Komunikacijos koordinatorius', 'en' => 'Communication coordinator'],
        'contacts_grouping' => 'none',
    ]);
    $holder = User::factory()->create([
        'name' => 'Jonas Jonaitis', 'pronouns' => ['lt' => '', 'en' => 'she / her'],
    ]);
    Dutiable::factory()->create([
        'duty_id' => $duty->id, 'dutiable_id' => $holder->id,
        'start_date' => now()->subMonth(), 'end_date' => null, 'use_original_duty_name' => false,
    ]);

    $page = loginAsAdmin(makeAdminUser($tenant));
    $screens = [
        'record' => '/mano/duties/'.$duty->id,
        'form' => '/mano/duties/'.$duty->id.'/edit',
        'user' => '/mano/users/'.$holder->id,
        'contacts' => '/lt/kontaktai/id/'.$institution->id,
    ];
    foreach ($screens as $screen => $path) {
        if ($screen === 'contacts') {
            $page = visitPublicSubdomain('www', $path);
            $page->click('[data-slot="cookie-consent"] button');
            $page->script('document.querySelector("[data-slot=public-contact-card]").scrollIntoView({block: "center"})');
        } else {
            $page->navigate($path);
            waitForInertiaRender($page, $screen === 'form' ? '[data-slot="form-page"] h1' : '[data-slot="record-title"]');
        }

        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');
            foreach ([390, 820, 1180, 1440] as $width) {
                $page->resize($width, 844);
                expect($page->script('document.documentElement.scrollWidth > innerWidth + 1'))->toBeFalse();
                if (getenv('DUTY_SCREENSHOTS') && (($width === 1440 && ! $dark) || ($width === 390 && $dark))) {
                    $page->screenshot(fullPage: false, filename: 'duty-'.$screen.'-'.$width.'-'.($dark ? 'dark' : 'light'));
                }
            }
        }
        if ($screen === 'form') {
            $page->fill('input#duty-name', 'Finansų koordinatorius');
            $page->assertSeeIn('[data-slot="form-page"] h1', 'Finansų koordinator');
            $page->assertSeeIn('[data-testid="form-page-bar-title"]', 'Komunikacijos koordinatorius');
            expect($page->script('document.querySelector("[data-testid=form-page-bar-title] [data-testid=duty-ending-trigger]") === null'))->toBeTrue();
            $page->fill('input#duty-name', 'Komunikacijos koordinatorius');
        }
        $page->assertNoJavaScriptErrors();
    }
});
