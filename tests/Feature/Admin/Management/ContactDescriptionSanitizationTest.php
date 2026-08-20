<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Duty, Dutiable and Institution descriptions are Tiptap `full` preset HTML that
 * the public site renders with `v-html` — ContactWithPhoto.vue for the two duty
 * descriptions, ShowInstitution.vue / InstitutionFigure.vue for the institution
 * one. Any padalinys-scoped admin may write them, so unsanitized markup stored
 * here would execute in every anonymous visitor's browser.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();
});

test('duty description is sanitized on write', function (): void {
    $duty = Duty::factory()->for($this->institution)->create([
        'description' => [
            'lt' => '<p>Geras tekstas</p><script>alert(1)</script>',
            'en' => '<p>Good text</p><img src=x onerror="alert(1)">',
        ],
    ]);

    expect($duty->fresh()->getTranslation('description', 'lt'))
        ->toContain('Geras tekstas')
        ->not->toContain('<script')
        ->and($duty->fresh()->getTranslation('description', 'en'))
        ->toContain('Good text')
        ->not->toContain('onerror');

    $duty->update(['description' => ['lt' => '<a href="javascript:alert(1)">x</a>']]);

    expect($duty->fresh()->getTranslation('description', 'lt'))->not->toContain('javascript:');
});

test('dutiable description is sanitized on write', function (): void {
    $dutiable = Dutiable::factory()->create([
        'duty_id' => $this->duty->id,
        'dutiable_id' => User::factory()->create()->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subDay(),
        'description' => ['lt' => '<p>Aprašymas</p><script>alert(1)</script>'],
    ]);

    expect($dutiable->fresh()->getTranslation('description', 'lt'))
        ->toContain('Aprašymas')
        ->not->toContain('<script');

    $dutiable->update(['description' => ['lt' => '<img src=x onerror="alert(1)">']]);

    expect($dutiable->fresh()->getTranslation('description', 'lt'))->not->toContain('onerror');
});

test('institution description is sanitized on write', function (): void {
    $institution = Institution::factory()->for($this->tenant)->create([
        'description' => ['lt' => '<p>Apie</p><script>alert(1)</script>'],
    ]);

    expect($institution->fresh()->getTranslation('description', 'lt'))
        ->toContain('Apie')
        ->not->toContain('<script');

    $institution->update(['description' => ['lt' => '<img src=x onerror="alert(1)">']]);

    expect($institution->fresh()->getTranslation('description', 'lt'))->not->toContain('onerror');
});

test('legitimate rich formatting survives the round trip', function (): void {
    $html = '<h2 id="a">Antraštė</h2><p><strong>Svarbu</strong></p>'
        .'<a href="https://vusa.lt">Nuoroda</a><img src="/uploads/a.png" alt="A">';

    $duty = Duty::factory()->for($this->institution)->create(['description' => ['lt' => $html]]);

    expect($duty->fresh()->getTranslation('description', 'lt'))
        ->toContain('<h2 id="a">Antraštė</h2>')
        ->toContain('<strong>Svarbu</strong>')
        ->toContain('href="https://vusa.lt"')
        ->toContain('src="/uploads/a.png"');
});

test('non-html translatable fields are left untouched', function (): void {
    $duty = Duty::factory()->for($this->institution)->create([
        'name' => ['lt' => 'Koordinatorius <ir> kita', 'en' => 'Coordinator <and> more'],
    ]);

    expect($duty->fresh()->getTranslation('name', 'lt'))->toBe('Koordinatorius <ir> kita');
});

test('duty description is sanitized through the controller', function (): void {
    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo(['duties.read.padalinys', 'duties.update.padalinys']);

    $dutyManager = makeUser($this->tenant);
    $dutyManager->duties()->first()->assignRole('Communication Coordinator');

    asUser($dutyManager)->patch(route('duties.update', $this->duty), [
        'name' => ['lt' => 'Pareigos', 'en' => 'Duty'],
        'description' => ['lt' => '<p>Tekstas</p><script>alert(1)</script>', 'en' => ''],
        'institution_id' => $this->institution->id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
    ])->assertRedirect();

    expect($this->duty->fresh()->getTranslation('description', 'lt'))
        ->toContain('Tekstas')
        ->not->toContain('<script');
});
