<?php

use App\Models\Navigation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Sequence;
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

    $page->page()->waitForSelector('button.aspect-square', ['timeout' => 15000]);
    $page->page()->locator('button.aspect-square')->first()->click();

    $page->page()->locator('button:has-text("Toliau")')->first()->click();
    $page->page()->waitForSelector('input[maxlength="125"]', ['timeout' => 10000]);
    $page->fill('input[maxlength="125"]', 'Testas');
    $page->page()->locator('button:has-text("Įterpti")')->first()->click();

    // Give Vue a tick to patch.
    $page->page()->waitForTimeout(1500);

    $state = $page->script(<<<'JS'
        (() => {
          const imgs = Array.from(document.querySelectorAll('form img'));
          return JSON.stringify(imgs.map((img) => ({
            src: img.getAttribute('src'),
            visible: img.getClientRects().length > 0,
            w: img.clientWidth,
            h: img.clientHeight,
            complete: img.complete,
            natural: img.naturalWidth,
          })));
        })()
    JS);

    dump($state);
    $page->screenshot(filename: 'nav-image-after-pick');
})->group('tmp');
