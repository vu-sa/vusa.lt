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
    $checkbox = FormField::factory()->for($form)->create([
        'type' => 'boolean', 'is_required' => false, 'order' => 3,
        'label' => ['lt' => 'Noriu gauti informaciją apie renginius', 'en' => 'Send me information about events'],
        'description' => null,
    ]);
    $page = visitPublicSubdomain('www', '/lt/registracija/validation-test');
    waitForInertiaRender($page, "#registration-field-{$email->id}");
    $page->click('[data-slot=cookie-consent] button');
    $page->type("#registration-field-{$email->id}", 'invalid');
    $page->script('document.activeElement.blur()');
    $page->assertPresent("#registration-field-{$email->id}-error");
    $page->assertMissing('[data-testid=registration-errors]');
    $page->click('form button[type=submit]');
    $page->assertPresent("#registration-field-{$choice->id}-error");
    $page->assertAttribute("#registration-field-{$email->id}", 'aria-invalid', 'true');
    $page->assertMissing('[data-sonner-toast][data-type=error]');
    expect($page->script('document.activeElement.id'))->toBe("registration-field-{$email->id}");
    $page->assertMissing('[data-testid=registration-errors]');
    expect(Registration::query()->count())->toBe(0);
    $page->click("label[for=registration-field-{$checkbox->id}]");
    $page->assertAttribute("#registration-field-{$checkbox->id}", 'aria-checked', 'true');

    File::ensureDirectoryExists(base_path('tests/Browser/Screenshots/migration'));
    foreach (['light', 'dark'] as $theme) {
        $page->script('document.documentElement.classList.'.($theme === 'dark' ? 'add' : 'remove').'("dark")');
        foreach ([390, 820, 1180, 1440] as $width) {
            $page->resize($width, 900)->screenshot(filename: "migration/registration-{$theme}-{$width}");
        }
    }

    $page->type("#registration-field-{$email->id}", 'student@example.com');
    $page->select("#registration-field-{$choice->id}", '0');
    $page->click('form button[type=submit]')->assertSee(__('registrations.success'));
    expect(Registration::query()->count())->toBe(1);
    $registration = Registration::query()->firstOrFail();
    expect($registration->fieldResponses()->where('form_field_id', $choice->id)->firstOrFail()->response['value'])->toBe(0);
    $page->assertNoJavaScriptErrors();
});
