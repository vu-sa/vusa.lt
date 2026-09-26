<?php

use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the home layout within the viewport at the redesign widths', function (): void {
    // A representative with real meetings and tasks: an empty home would hide overflow bugs.
    $this->seed(DocsSeeder::class);
    $page = loginAsAdmin(User::query()->firstWhere('email', DocsSeeder::REPRESENTATIVE_EMAIL));
    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot=overview-page]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);

        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');

            $page->screenshot(fullPage: false, filename: 'admin-home-'.$width.($dark ? '-dark' : '-light'));

            if (! $dark && in_array($width, [390, 1440], true)) {
                docsScreenshot($page, $width === 390 ? 'admin-home-phone' : 'admin-home');
            }

            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width)
                ->and($page->script('document.querySelector("[data-slot=overview-page]").getBoundingClientRect().width <= window.innerWidth'))->toBeTrue();

            // Phones swap the top bar for the full-bleed context bar, which has no measure to match.
            if ($width >= 768) {
                $alignment = $page->script('(() => {
                    const bar = document.querySelector("[data-slot=shell-top-bar] > div");
                    const page = document.querySelector("[data-slot=admin-page-measure]");
                    const inset = element => {
                        const rect = element.getBoundingClientRect();
                        const style = getComputedStyle(element);
                        return [rect.left + parseFloat(style.paddingLeft), rect.right - parseFloat(style.paddingRight)];
                    };
                    return { bar: inset(bar), page: inset(page) };
                })()');

                expect(abs($alignment['bar'][0] - $alignment['page'][0]))->toBeLessThanOrEqual(1)
                    ->and(abs($alignment['bar'][1] - $alignment['page'][1]))->toBeLessThanOrEqual(1);
            }
        }
    }

    $page->assertNoJavaScriptErrors();
});
