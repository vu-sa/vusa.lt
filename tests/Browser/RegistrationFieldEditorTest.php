<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

pest()->use(RefreshDatabase::class);

it('adds and saves a registration field using native sheet controls', function (): void {
    $tenant = Tenant::query()->first();
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $form = Form::factory()->for($tenant)->create();
    pest()->browser()->withHost('www.vusa.test');
    $page = loginAsAdmin($user, "/mano/forms/{$form->id}/edit", 1440, 900);
    waitForInertiaRender($page, '[data-testid=add-form-field]');
    $page->click('[data-testid=add-form-field]');
    $page->type('[data-testid=form-field-label] input', 'Kontaktinis el. paštas');
    $page->select('select#type', 'string')->select('select#subtype', 'email');

    File::ensureDirectoryExists(base_path('tests/Browser/Screenshots/migration'));
    foreach (['light', 'dark'] as $theme) {
        $page->script('document.documentElement.classList.'.($theme === 'dark' ? 'add' : 'remove').'("dark")');
        foreach ([390, 1440] as $width) {
            $page->resize($width, 900)->screenshot(filename: "migration/field-sheet-{$theme}-{$width}");
        }
    }

    $page->click('[data-testid=form-field-sheet] button:has-text("Pateikti")');
    $page->assertMissing('[data-testid=form-field-sheet]');
    $page->click('[data-testid=form-page-save]');
    $page->assertSee(__('messages.updated.f', ['model' => trans_choice('entities.form.model', 1)]));
    $field = $form->formFields()->firstOrFail();
    expect($field->type)->toBe('string')
        ->and($field->subtype)->toBe('email')
        ->and($field->getTranslation('label', 'lt'))->toBe('Kontaktinis el. paštas');
    $page->assertNoJavaScriptErrors();
});
