<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

it('validates image details before inserting through the external submit button', function (): void {
    Storage::fake();
    $tenant = Tenant::query()->first();
    $path = 'files/padaliniai/vusa'.$tenant->alias.'/migration.png';
    Storage::put('public/'.$path, UploadedFile::fake()->image('migration.png')->getContent());
    Route::get('/uploads/'.$path, fn () => response(Storage::get('public/'.$path), 200, ['Content-Type' => 'image/png']));
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $form = Form::factory()->for($tenant)->create();
    $page = loginAsAdmin($user, "/mano/forms/{$form->id}/edit", 1440, 900);
    waitForInertiaRender($page, '[data-testid=tiptap-insert-menu]');
    $page->click('[data-testid=tiptap-insert-menu]')->click('[data-testid=tiptap-insert-image]');
    $page->click('[data-slot=file-item] button[aria-label="migration.png"]');
    $page->click('[data-slot=file-details] button:has-text("Įterpti")');
    $page->click('[data-slot=file-details] button:has-text("Uždaryti")');
    $page->click('[role=dialog] button:has-text("Toliau")');
    $page->assertPresent('#image-alt');
    $page->click('button[form=image-details-form]')->assertPresent('#image-alt-error');

    $page->type('#image-alt', '  Studentai renginyje  ')->type('#image-title', '  Renginys  ');
    $page->click('button[form=image-details-form]');
    $page->assertMissing('#image-details-form')->assertPresent('.tiptap img[alt="Studentai renginyje"][title=Renginys]');
    $page->assertNoJavaScriptErrors();
});
