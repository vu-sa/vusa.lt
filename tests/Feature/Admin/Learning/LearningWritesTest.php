<?php

use App\Actions\MergeDuties;
use App\Actions\MergeUsers;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\HandoverNote;
use App\Models\LearningSection;
use App\Models\LearningStep;
use App\Models\LearningStepAudience;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LearningAdminPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('system updates ignore authored fields and preserve their identity', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::query()->where('key', 'check_access')->firstOrFail();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'audiences' => [['id' => $step->audiences->first()->id, 'stage' => 'whole_term', 'order' => 2]], 'order' => 6, 'is_active' => false,
        'title' => ['lt' => 'Injected'], 'key' => 'other', 'is_system' => false,
    ])->assertRedirect();
    $step->refresh();
    expect($step->key)->toBe('check_access');
    expect($step->is_system)->toBeTrue();
    expect($step->audiences->first()->stage)->toBe(LearningStage::WholeTerm);
    expect($step->order)->toBe(6);
    expect($step->is_active)->toBeFalse();
    expect($step->getTranslations('title'))->toBeEmpty();
});

test('step writes reject incompatible targets and ambiguous sources', function (array $changes, string $error): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $payload = ['kind' => 'action', 'audiences' => [['type' => 'everyone', 'stage' => 'before_start', 'importance' => 'recommended']], 'title' => ['lt' => 'Žingsnis', 'en' => 'Step']];
    asUser($user)->postJson(route('learningSteps.store'), array_replace($payload, $changes))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'missing type' => [['audiences' => [['type' => 'institution_type', 'stage' => 'before_start', 'importance' => 'recommended']]], 'audiences.0.source_id'],
    'meeting prep stage' => [['kind' => 'meeting_prep'], 'audiences.0.stage'],
    'document stage' => [['kind' => 'knowledge'], 'audiences.0.stage'],
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

test('handover writes resolve the authors cadence from term start and preserve plain text', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    $old = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subYear(), 'end_date' => today()->subDays(10)]);
    Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subDays(9), 'end_date' => today()->addYear()]);
    $term = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subYear(), 'end_date' => today()->subDays(10)]);
    $text = "Pirma eilutė\nAntra eilutė <script>plain text</script>";
    asUser($user)->post(route('handoverNotes.store', $duty), [
        'situation' => $text, 'people' => 'Rūta', 'user_id' => 'other', 'dutiable_id' => 'other', 'cadence_id' => 'other',
    ])->assertRedirect();
    $note = HandoverNote::query()->where('duty_id', $duty->id)->sole();
    expect($note->user_id)->toBe($user->id);
    expect($note->dutiable_id)->toBe($term->id);
    expect($note->cadence_id)->toBe($old->id);
    expect($note->situation)->toBe($text);
    asUser($user)->patch(route('handoverNotes.update', $note), ['advice' => 'Pasitark su koordinatoriumi.'])->assertRedirect();
    expect($note->fresh()->advice)->toBe('Pasitark su koordinatoriumi.');
});

test('saving twice in one cadence upserts even if the holders dated term row changes', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    $cadence = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subYear(), 'end_date' => today()->addYear()]);
    $first = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subMonth(), 'end_date' => today()->subDays(2)]);
    asUser($user)->post(route('handoverNotes.store', $duty), ['situation' => 'Pirma'])->assertRedirect();
    $id = HandoverNote::query()->where('duty_id', $duty->id)->sole()->id;
    $second = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subDay(), 'end_date' => null]);
    asUser($user)->post(route('handoverNotes.store', $duty), ['situation' => 'Antra'])->assertRedirect();
    $note = HandoverNote::query()->where('duty_id', $duty->id)->sole();
    expect($note->id)->toBe($id);
    expect($note->cadence_id)->toBe($cadence->id);
    expect($note->dutiable_id)->toBe($second->id)->not->toBe($first->id);
    expect($note->situation)->toBe('Antra');
});

test('handover without an available cadence keeps one entry and labels it with term years', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    $term = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subMonth(), 'end_date' => today()->addYear()]);
    asUser($user)->post(route('handoverNotes.store', $duty), ['people' => 'Rūta'])->assertRedirect();
    asUser($user)->post(route('handoverNotes.store', $duty), ['people' => 'Jonas'])->assertRedirect();
    $note = HandoverNote::query()->where('duty_id', $duty->id)->sole();
    expect($note->cadence_id)->toBeNull();
    expect($note->people)->toBe('Jonas');
    $years = implode('–', array_unique([$term->start_date->year, $term->end_date->year]));
    expect(LearningAdminPresenter::note($note, 'lt')['cadenceLabel'])->toBe($years);
});

test('handover fields require some content and cap every field at five thousand characters', function (array $payload, array $errors): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $duty = $user->current_duties()->firstOrFail();
    asUser($user)->postJson(route('handoverNotes.store', $duty), $payload)->assertUnprocessable()->assertJsonValidationErrors($errors);
    expect(HandoverNote::query()->where('duty_id', $duty->id)->exists())->toBeFalse();
})->with([
    'empty' => [[], ['situation', 'open_issues', 'people', 'resources', 'advice']],
    'blank' => [['situation' => " \n ", 'people' => null], ['situation']],
    'too long' => [array_fill_keys(['situation', 'open_issues', 'people', 'resources', 'advice'], str_repeat('ą', 5001)), ['situation', 'open_issues', 'people', 'resources', 'advice']],
    'wrong type' => [['resources' => ['url']], ['resources']],
]);

test('any single handover field is sufficient on store and update', function (string $field): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $duty = $user->current_duties()->firstOrFail();
    asUser($user)->postJson(route('handoverNotes.store', $duty), [$field => str_repeat('ą', 5000)])->assertRedirect();
    $note = HandoverNote::query()->where('duty_id', $duty->id)->sole();
    asUser($user)->patchJson(route('handoverNotes.update', $note), [])->assertUnprocessable()->assertJsonValidationErrors('situation');
    asUser($user)->patch(route('handoverNotes.update', $note), [$field => 'Papildyta'])->assertRedirect();
    expect($note->fresh()->getAttribute($field))->toBe('Papildyta');
})->with(['situation', 'open_issues', 'people', 'resources', 'advice']);

test('merging duplicate users preserves distinct cadence notes and keeps the existing twin', function (): void {
    $kept = User::factory()->create();
    $source = User::factory()->create();
    $duty = Duty::factory()->create();
    $cadence = Cadence::factory()->forYear(2025)->create();
    $other = Cadence::factory()->forYear(2026)->create();
    $keptNote = HandoverNote::factory()->for($kept, 'author')->for($duty)->create(['cadence_id' => $cadence->id]);
    HandoverNote::factory()->for($source, 'author')->for($duty)->create(['cadence_id' => $cadence->id]);
    $moved = HandoverNote::factory()->for($source, 'author')->for($duty)->create(['cadence_id' => $other->id]);
    MergeUsers::execute($kept, collect([$source]), $kept->email);
    expect($keptNote->fresh())->not->toBeNull();
    expect($moved->fresh()->user_id)->toBe($kept->id);
    expect(HandoverNote::query()->where('duty_id', $duty->id)->count())->toBe(2);
});

test('merging duplicate duties preserves distinct cadence notes and keeps the existing twin', function (): void {
    $kept = Duty::factory()->create();
    $source = Duty::factory()->create(['institution_id' => $kept->institution_id]);
    $author = User::factory()->create();
    $cadence = Cadence::factory()->forYear(2025)->create();
    $other = Cadence::factory()->forYear(2026)->create();
    $keptNote = HandoverNote::factory()->for($kept)->for($author, 'author')->create(['cadence_id' => $cadence->id]);
    HandoverNote::factory()->for($source)->for($author, 'author')->create(['cadence_id' => $cadence->id]);
    $moved = HandoverNote::factory()->for($source)->for($author, 'author')->create(['cadence_id' => $other->id]);
    MergeDuties::execute($kept, collect([$source]));
    expect($keptNote->fresh())->not->toBeNull();
    expect($moved->fresh()->duty_id)->toBe($kept->id);
    expect(HandoverNote::query()->where('user_id', $author->id)->count())->toBe(2);
});

test('replacing audiences removes the previous target', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::factory()->forTenant(Tenant::query()->firstOrFail())->create();
    $old = $step->audiences->sole()->id;
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'action', 'title' => ['lt' => 'Veiksmas', 'en' => 'Action'],
        'audiences' => [['type' => 'everyone', 'stage' => 'whole_term', 'importance' => 'recommended']],
    ])->assertRedirect();
    $this->assertDatabaseMissing('learning_step_audiences', ['id' => $old]);
    expect($step->fresh()->audiences->sole()->tenant_id)->toBeNull();
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
        'kind' => 'knowledge', 'title' => ['lt' => 'Žingsnis', 'en' => 'Step'],
        'audiences' => [['type' => 'everyone', 'tenant_id' => $other->id, 'importance' => 'recommended']],
    ])->assertForbidden();
    expect($step->fresh()->audiences->sole()->tenant_id)->toBe($tenant->id);
});

test('removing a cadence keeps the handover text and falls back to the holders term years', function (): void {
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    $cadence = Cadence::factory()->forYear(2026)->create();
    $term = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
    $note = HandoverNote::factory()->for($user, 'author')->for($duty)->create(['cadence_id' => $cadence->id, 'dutiable_id' => $term->id, 'situation' => "First line\nSecond line"]);
    $cadence->delete();
    $note->refresh();
    expect($note->cadence_id)->toBeNull();
    expect($note->situation)->toBe("First line\nSecond line");
    expect(LearningAdminPresenter::note($note, 'lt')['cadenceLabel'])->toBe('2026–2027');
});

test('an audience id from another step is refused and the transaction leaves both steps intact', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::factory()->create();
    $other = LearningStep::factory()->create();
    $foreign = $other->audiences->sole();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'action', 'title' => ['lt' => 'Pakeista', 'en' => 'Changed'],
        'audiences' => [['id' => $foreign->id, 'type' => 'everyone', 'stage' => 'before_start', 'importance' => 'essential']],
    ])->assertForbidden();

    expect($step->fresh()->getTranslations('title'))->toBe($step->getTranslations('title'));
    expect($foreign->fresh()->learning_step_id)->toBe($other->id);
    expect($foreign->fresh()->importance)->toBe(LearningImportance::Recommended);
});

test('system audiences preserve identity importance and omitted rows', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::query()->where('key', 'meet_coordinator')->sole();
    $audience = $step->audiences->first();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'audiences' => [['id' => $audience->id, 'stage' => 'whole_term', 'order' => 7]],
    ])->assertRedirect();
    expect($step->fresh()->audiences)->toHaveCount(2);
    expect($audience->fresh()->stage)->toBe(LearningStage::WholeTerm);
    expect($audience->fresh()->importance)->toBe(LearningImportance::Essential);
    asUser($user)->patchJson(route('learningSteps.update', $step), [
        'audiences' => [['id' => $audience->id, 'type' => 'everyone', 'importance' => 'optional']],
    ])->assertUnprocessable();
});

test('authored audience validation rejects empty targeting system purposes and missing stages', function (array $audiences, string $error): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    asUser($user)->postJson(route('learningSteps.store'), [
        'kind' => 'action', 'title' => ['lt' => 'Veiksmas', 'en' => 'Action'], 'audiences' => $audiences,
    ])->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'empty audiences' => [[], 'audiences'],
    'unknown purpose' => [[['type' => 'purpose', 'source_id' => 'nonexistent', 'importance' => 'essential', 'stage' => 'before_start']], 'audiences.0.source_id'],
    'missing stage' => [[['type' => 'everyone', 'importance' => 'recommended']], 'audiences.0.stage'],
]);

test('knowledge sections are cleared when a step becomes an action', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $section = LearningSection::factory()->create();
    $step = LearningStep::factory()->create(['learning_section_id' => $section->id]);
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'action', 'title' => ['lt' => 'Veiksmas', 'en' => 'Action'], 'learning_section_id' => $section->id,
        'audiences' => [['type' => 'everyone', 'importance' => 'recommended', 'stage' => 'before_start']],
    ])->assertRedirect();
    expect($step->fresh()->learning_section_id)->toBeNull();
});

test('merging duties preserves learning audiences targeting the merged seat', function (): void {
    $kept = Duty::factory()->create();
    $source = Duty::factory()->create();
    $audience = LearningStepAudience::factory()->duty($source)->create();
    MergeDuties::execute($kept, collect([$source]));
    expect($audience->fresh()->source_id)->toBe($kept->id);
});
