<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('keeps the related-record picker usable across admin viewports and themes', function (): void {
    $page = loginAsAdmin(makeAdminUser(Tenant::query()->first()));
    $page->navigate('/mano/settings/site');
    waitForInertiaRender($page);
    $page->resize(390, 844);
    $page->click('#privacy_page_id_lt');
    $page->assertPresent('[data-slot="dialog-content"] input[aria-label]');
    $page->wait(0.3);

    $surfaceColours = [];

    foreach ([false, true] as $dark) {
        $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');

        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 844);
            $page->wait(0.2);

            $layout = $page->script(<<<'JS'
                (() => {
                  const dialog = document.querySelector('[data-slot="dialog-content"]');
                  const footer = dialog.querySelector('[data-slot="dialog-footer"]');
                  const bounds = dialog.getBoundingClientRect();
                  const footerBounds = footer.getBoundingClientRect();
                  return {
                    left: bounds.left,
                    right: bounds.right,
                    top: bounds.top,
                    bottom: bounds.bottom,
                    footerBottom: footerBounds.bottom,
                    background: getComputedStyle(dialog).backgroundColor,
                  };
                })()
            JS);

            expect($layout['left'])->toBeGreaterThanOrEqual(-1)
                ->and($layout['right'])->toBeLessThanOrEqual($width + 1)
                ->and($layout['top'])->toBeGreaterThanOrEqual(-1)
                ->and($layout['bottom'])->toBeLessThanOrEqual(845)
                ->and($layout['footerBottom'])->toBeLessThanOrEqual(845);

            $surfaceColours[$dark ? 'dark' : 'light'] = $layout['background'];
        }
    }

    expect($surfaceColours['light'])->not->toBe($surfaceColours['dark']);
    $page->assertNoJavaScriptErrors();
});
