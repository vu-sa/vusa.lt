<?php

use App\Enums\LearningStage;
use App\Models\GlossaryTerm;
use App\Models\HandoverNote;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('system updates ignore authored fields and preserve their identity', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::query()->where('key', 'check_access')->firstOrFail();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'stage' => 'whole_term', 'order' => 6, 'is_active' => false,
        'title' => ['lt' => 'Injected'], 'key' => 'other', 'is_system' => false, 'layer' => 'tenant',
    ])->assertRedirect();
    $step->refresh();
    expect($step->key)->toBe('check_access');
    expect($step->is_system)->toBeTrue();
    expect($step->stage)->toBe(LearningStage::WholeTerm);
    expect($step->order)->toBe(6);
    expect($step->is_active)->toBeFalse();
    expect($step->getTranslations('title'))->toBeEmpty();
});

test('step writes reject incompatible targets and ambiguous sources', function (array $changes, string $error): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $payload = ['kind' => 'knowledge', 'layer' => 'platform', 'stage' => 'before_start', 'importance' => 'recommended', 'title' => ['lt' => 'Žingsnis', 'en' => 'Step']];
    asUser($user)->postJson(route('learningSteps.store'), array_replace($payload, $changes))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'missing type' => [['layer' => 'institution_type'], 'institution_type_id'],
    'meeting prep stage' => [['kind' => 'meeting_prep'], 'stage'],
    'missing title' => [['title' => ['en' => 'Step']], 'title.lt'],
    'empty source' => [['sources' => ['lt' => []]], 'sources.lt'],
    'unsafe URL' => [['sources' => ['lt' => ['url' => 'javascript:alert(1)']]], 'sources.lt.url'],
]);

test('a global curator can create update and delete a glossary term', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $payload = ['term' => ['lt' => 'Kadencija', 'en' => 'Term'], 'definition' => ['lt' => 'Pareigų laikotarpis.', 'en' => 'A period in office.']];
    asUser($user)->post(route('glossaryTerms.store'), $payload)->assertRedirect();
    $term = GlossaryTerm::query()->latest()->firstOrFail();
    $payload['definition']['lt'] = 'Institucijos laikotarpis.';
    asUser($user)->patch(route('glossaryTerms.update', $term), $payload)->assertRedirect();
    expect($term->fresh()->getTranslation('definition', 'lt'))->toBe('Institucijos laikotarpis.');
    asUser($user)->delete(route('glossaryTerms.destroy', $term))->assertRedirect();
    $this->assertDatabaseMissing('glossary_terms', ['id' => $term->id]);
});

test('handover writes bind author and term server side and sanitize the body', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $duty = $user->current_duties()->firstOrFail();
    asUser($user)->post(route('handoverNotes.store', $duty), ['body' => '<p>Pastaba</p><script>alert(1)</script>', 'user_id' => 'other', 'dutiable_id' => 'other'])->assertRedirect();
    $note = HandoverNote::query()->where('duty_id', $duty->id)->firstOrFail();
    expect($note->user_id)->toBe($user->id);
    expect($note->dutiable_id)->toBe($duty->pivot->id);
    expect($note->body)->toContain('Pastaba')->not->toContain('<script');
    asUser($user)->patch(route('handoverNotes.update', $note), ['body' => '<p>Papildyta</p>'])->assertRedirect();
    expect($note->fresh()->body)->toBe('<p>Papildyta</p>');
});

test('saving the same handover twice keeps one note for the term', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $duty = $user->current_duties()->firstOrFail();
    asUser($user)->post(route('handoverNotes.store', $duty), ['body' => '<p>Pirma</p>'])->assertRedirect();
    asUser($user)->post(route('handoverNotes.store', $duty), ['body' => '<p>Antra</p>'])->assertRedirect();
    expect(HandoverNote::query()->where('duty_id', $duty->id)->pluck('body')->all())->toBe(['<p>Antra</p>']);
});

test('moving a step to another layer clears the previous target', function (): void {
    $step = LearningStep::factory()->forTenant(Tenant::query()->firstOrFail())->create();
    $step->update(['layer' => 'platform']);
    expect($step->fresh()->tenant_id)->toBeNull();
});

test('members cannot mutate the library or glossary', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create();
    $term = GlossaryTerm::factory()->create();
    asUser($user)->patch(route('learningSteps.update', $step), [])->assertForbidden();
    asUser($user)->delete(route('learningSteps.destroy', $step))->assertForbidden();
    asUser($user)->post(route('glossaryTerms.store'), [])->assertForbidden();
    asUser($user)->delete(route('glossaryTerms.destroy', $term))->assertForbidden();
});

test('a tenant curator cannot move an editable step into another unit', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.padalinys', 'web'));
    $step = LearningStep::factory()->forTenant($tenant)->create();
    $other = Tenant::factory()->create();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'knowledge', 'layer' => 'tenant', 'tenant_id' => $other->id,
        'stage' => 'before_start', 'importance' => 'recommended', 'title' => ['lt' => 'Žingsnis', 'en' => 'Step'],
    ])->assertForbidden();
    expect($step->fresh()->tenant_id)->toBe($tenant->id);
});
