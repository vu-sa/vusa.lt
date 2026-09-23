<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the home layout within the viewport at the redesign widths', function (): void {
    $user = makeAdminUser(Tenant::query()->first());
    $page = loginAsAdmin($user);
    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot=overview-page]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);

        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');

            $page->screenshot(fullPage: false, filename: 'admin-home-'.$width.($dark ? '-dark' : '-light'));

            expect($page->script('document.documentElement.scrollWidth'))->toBeLessThanOrEqual($width);
            expect($page->script('document.querySelector("[data-slot=overview-page]").getBoundingClientRect().width <= window.innerWidth'))->toBeTrue();

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

    $page->assertNoJavaScriptErrors();
});
