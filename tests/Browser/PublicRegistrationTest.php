<?php

use App\Models\Form;
use App\Models\FormField;
use App\Models\Registration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

pest()->use(RefreshDatabase::class);

it('validates and submits a public registration through the built frontend', function (): void {
    $form = Form::factory()->published()->create([
        'tenant_id' => Tenant::query()->first()->id,
        'name' => ['lt' => 'Registracijos patikra', 'en' => 'Registration check'],
        'path' => ['lt' => 'validation-test', 'en' => 'validation-test'],
    ]);
    $email = FormField::factory()->for($form)->create([
        'type' => 'string', 'subtype' => 'email', 'is_required' => true, 'order' => 1,
        'label' => ['lt' => 'El. paštas', 'en' => 'Email'],
        'description' => ['lt' => 'Įrašyk savo el. paštą.', 'en' => 'Enter your email.'],
    ]);
    $choice = FormField::factory()->for($form)->create([
        'type' => 'enum', 'is_required' => true, 'order' => 2,
        'label' => ['lt' => 'Pasirinkimas', 'en' => 'Choice'],
        'options' => [['value' => 0, 'label' => ['lt' => 'Pirmas', 'en' => 'First']], ['value' => 2, 'label' => ['lt' => 'Antras', 'en' => 'Second']]],
    ]);
    $page = visitPublicSubdomain('www', '/lt/registracija/validation-test');
    waitForInertiaRender($page, "#registration-field-{$email->id}");
    $page->click('[data-slot=cookie-consent] button');
    $page->type("#registration-field-{$email->id}", 'invalid');
    $page->script('document.activeElement.blur()');
    $page->assertPresent("#registration-field-{$email->id}-error");
    expect(Registration::query()->count())->toBe(0);

    File::ensureDirectoryExists(base_path('tests/Browser/Screenshots/migration'));
    foreach (['light', 'dark'] as $theme) {
        $page->script('document.documentElement.classList.'.($theme === 'dark' ? 'add' : 'remove').'("dark")');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900)->screenshot(filename: "migration/registration-{$theme}-{$width}");
        }
    }

    $page->type("#registration-field-{$email->id}", 'student@example.com');
    $page->click("#registration-field-{$choice->id}")->click('[role=option]:has-text("Pirmas")');
    $page->click('form button[type=submit]')->assertSee(__('registrations.success'));
    expect(Registration::query()->count())->toBe(1);
    $registration = Registration::query()->firstOrFail();
    expect($registration->fieldResponses()->where('form_field_id', $choice->id)->firstOrFail()->response['value'])->toBe(0);
    $page->assertNoJavaScriptErrors();
});
