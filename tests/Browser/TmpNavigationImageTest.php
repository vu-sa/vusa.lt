<?php

use App\Models\Navigation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('shows the image preview right after picking a file', function (): void {
    $admin = makeAdminUser(Tenant::query()->first());
    $parent = Navigation::factory()->root()->create();
    $link = Navigation::factory()->child($parent)->create(['extra_attributes' => []]);

    $page = loginAsAdmin($admin);

    $page->navigate("/mano/navigation/{$link->id}/edit");
    waitForInertiaRender($page, 'form');

    $page->click('button:has-text("Rodyti paveikslėlio nustatymus")');
    $page->page()->waitForSelector('button:has-text("Įkelti paveikslėlį")', ['timeout' => 10000]);
    $page->click('button:has-text("Įkelti paveikslėlį")');

    $firstFile = $page->page()->locator('button.aspect-square')->first();
    $firstFile->waitFor(['state' => 'visible', 'timeout' => 15000]);
    $firstFile->click();

    $page->page()->locator('button:has-text("Toliau")')->first()->click();
    $page->page()->waitForSelector('input[maxlength="125"]', ['timeout' => 10000]);
    $page->fill('input[maxlength="125"]', 'Testas');
    $page->page()->locator('button:has-text("Įterpti")')->first()->click();

    $page->page()->waitForSelector('input[maxlength="125"]', ['state' => 'detached', 'timeout' => 10000]);

    $hasVisiblePreview = $page->script(<<<'JS'
        (() => {
          const imgs = Array.from(document.querySelectorAll('form img'));
          return imgs.some((img) => img.getAttribute('src')
            && img.getClientRects().length > 0
            && img.clientWidth > 0
            && img.clientHeight > 0
            && img.complete
            && img.naturalWidth > 0);
        })()
    JS);

    expect($hasVisiblePreview)->toBeTrue();
    $page->assertNoJavaScriptErrors();
})->group('tmp');
