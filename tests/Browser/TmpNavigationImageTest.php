<?php

use App\Models\Navigation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

const BROWSER_TEST_IMAGE_PATH = 'public/files/000-browser-test-image.png';

beforeEach(function (): void {
    Storage::put(
        BROWSER_TEST_IMAGE_PATH,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL+XQAAAABJRU5ErkJggg==', strict: true),
    );
});

afterEach(function (): void {
    Storage::delete(BROWSER_TEST_IMAGE_PATH);
});

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

    $testImage = $page->page()->locator('button:has-text("000-browser-test-image.png")');
    $testImage->waitFor(['state' => 'visible', 'timeout' => 15000]);
    $testImage->click();

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
