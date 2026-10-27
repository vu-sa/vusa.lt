<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

it('validates image details before inserting through the external submit button', function (): void {
    Storage::fake();
    $tenant = Tenant::query()->first();
    Storage::put('public/files/padaliniai/vusa'.$tenant->alias.'/migration.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jZ1kAAAAASUVORK5CYII='));
    $path = 'files/padaliniai/vusa'.$tenant->alias.'/migration.png';
    Route::get('/uploads/'.$path, fn () => response(Storage::get('public/'.$path), 200, ['Content-Type' => 'image/png']));
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $form = Form::factory()->for($tenant)->create();
    pest()->browser()->withHost('www.vusa.test');
    $page = loginAsAdmin($user, "/mano/forms/{$form->id}/edit", 1440, 900);
    waitForInertiaRender($page, '[data-testid=tiptap-insert-menu]');
    $page->click('[data-testid=tiptap-insert-menu]')->click('[data-testid=tiptap-insert-image]');
    $page->click('[data-slot=file-item] button[aria-label="migration.png"]');
    $page->click('[data-slot=file-details] button:has-text("Įterpti")');
    $page->click('[data-slot=file-details] button:has-text("Close")');
    $page->click('[role=dialog] button:has-text("Toliau")');
    $page->assertPresent('#image-alt');
    $page->click('button[form=image-details-form]')->assertPresent('#image-alt-error');

    File::ensureDirectoryExists(base_path('tests/Browser/Screenshots/migration'));
    foreach (['light', 'dark'] as $theme) {
        $page->script('document.documentElement.classList.'.($theme === 'dark' ? 'add' : 'remove').'("dark")');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900)->screenshot(filename: "migration/image-{$theme}-{$width}");
        }
    }

    $page->type('#image-alt', '  Studentai renginyje  ')->type('#image-title', '  Renginys  ');
    $page->click('button[form=image-details-form]');
    $page->assertMissing('#image-details-form')->assertPresent('.tiptap img[alt="Studentai renginyje"][title=Renginys]');
    $page->assertNoJavaScriptErrors();
});
