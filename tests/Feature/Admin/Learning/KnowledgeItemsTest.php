<?php

use App\Actions\ResolveLearningPath;
use App\Enums\LearningImportance;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function knowledgePayload(?int $tenantId = null): array
{
    return [
        'title' => ['lt' => 'Atstovo žinios', 'en' => 'Representative knowledge'],
        'summary' => ['lt' => 'Ką atstovas turi žinoti.', 'en' => 'What a rep should know.'],
        'tenant_id' => $tenantId,
        'description' => ['lt' => '<p>Žinok savo teises.</p>', 'en' => '<p>Know your rights.</p>'],
        'audiences' => [['type' => 'everyone', 'tenant_id' => $tenantId, 'importance' => 'recommended']],
        'sources' => ['lt' => ['url' => 'https://example.test/knowledge']],
        'per_term' => true, 'order' => 4, 'is_active' => true,
    ];
}

test('knowledge curators create update and delete items with their related data', function (): void {
    $this->freezeSecond();
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['create', 'update', 'delete'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.*", 'web'));
    }
    $section = KnowledgeSection::factory()->create();
    $payload = [...knowledgePayload(), 'knowledge_section_id' => $section->id];

    asUser($user)->post(route('knowledgeItems.store'), $payload)->assertRedirect();
    $item = KnowledgeItem::query()->where('created_by', $user->id)->sole();
    expect($item->section->is($section))->toBeTrue()
        ->and($item->per_term)->toBeTrue()
        ->and($item->reviewed_at->equalTo(now()))->toBeTrue()
        ->and($item->sources->sole()->url)->toBe('https://example.test/knowledge')
        ->and($item->audiences->sole()->stage)->toBeNull();

    asUser($user)->put(route('knowledgeItems.update', $item), [
        ...$payload, 'title' => ['lt' => 'Atnaujinta', 'en' => 'Updated'],
        'sources' => ['lt' => ['url' => 'https://example.test/updated']],
    ])->assertRedirect();
    expect($item->fresh()->getTranslation('title', 'en'))->toBe('Updated')
        ->and($item->fresh()->sources->sole()->url)->toBe('https://example.test/updated');

    $audience = $item->audiences->sole();
    $item->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()]);
    asUser($user)->delete(route('knowledgeItems.destroy', $item))->assertRedirect(route('knowledgeItems.index'));
    $this->assertModelMissing($item);
    $this->assertModelMissing($audience);
    $this->assertDatabaseMissing('learning_progress', ['progressable_type' => $item->getMorphClass(), 'progressable_id' => $item->id]);
});

test('members cannot write knowledge items', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->create();
    asUser($user)->post(route('knowledgeItems.store'), knowledgePayload())->assertForbidden();
    asUser($user)->put(route('knowledgeItems.update', $item), knowledgePayload())->assertForbidden();
    asUser($user)->delete(route('knowledgeItems.destroy', $item))->assertForbidden();
    $this->assertModelExists($item);
});

test('tenant curators must keep every audience and section within their unit', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $other = Tenant::query()->whereKeyNot($tenant->id)->firstOrFail();
    $user = makeUser($tenant);
    foreach (['create', 'update', 'delete'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.padalinys", 'web'));
    }
    $own = KnowledgeItem::factory()->forTenant($tenant)->create();
    $foreign = KnowledgeItem::factory()->forTenant($other)->create();
    $global = KnowledgeItem::factory()->create();
    $foreignSection = KnowledgeSection::factory()->create(['tenant_id' => $other->id]);
    expect($user->can('update', $own))->toBeTrue()
        ->and($user->can('update', $foreign))->toBeFalse()
        ->and($user->can('update', $global))->toBeFalse();
    asUser($user)->post(route('knowledgeItems.store'), knowledgePayload())->assertForbidden();
    asUser($user)->post(route('knowledgeItems.store'), knowledgePayload($other->id))->assertForbidden();
    asUser($user)->put(route('knowledgeItems.update', $own), knowledgePayload($other->id))->assertForbidden();
    asUser($user)->put(route('knowledgeItems.update', $foreign), knowledgePayload($tenant->id))->assertForbidden();
    asUser($user)->delete(route('knowledgeItems.destroy', $foreign))->assertForbidden();
    asUser($user)->post(route('knowledgeItems.store'), [...knowledgePayload($tenant->id), 'knowledge_section_id' => $foreignSection->id])->assertForbidden();
    expect($own->fresh()->audiences->sole()->tenant_id)->toBe($tenant->id);
    asUser($user)->post(route('knowledgeItems.store'), knowledgePayload($tenant->id))->assertRedirect();
});

test('knowledge rejects stages and requires an unambiguous Lithuanian source', function (array $changes, string $error): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    asUser($user)->postJson(route('knowledgeItems.store'), array_replace(knowledgePayload(), $changes))
        ->assertUnprocessable()->assertJsonValidationErrors($error);
    $this->assertDatabaseMissing('knowledge_items', ['created_by' => $user->id]);
})->with([
    'stage' => [['audiences' => [['type' => 'everyone', 'importance' => 'recommended', 'stage' => 'whole_term']]], 'audiences.0.stage'],
    'missing lt' => [['sources' => ['en' => ['url' => 'https://example.test/en']]], 'sources.lt'],
    'empty source' => [['sources' => ['lt' => ['url' => null]]], 'sources.lt'],
    'unsafe url' => [['sources' => ['lt' => ['url' => 'javascript:alert(1)']]], 'sources.lt.url'],
]);

test('foreign audience ids roll back knowledge edits', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $item = KnowledgeItem::factory()->create();
    $step = LearningStep::factory()->create();
    $audience = $step->audiences->sole();
    $payload = knowledgePayload();
    $payload['audiences'][0]['id'] = $audience->id;
    asUser($user)->put(route('knowledgeItems.update', $item), $payload)->assertForbidden();
    expect($item->fresh()->getTranslations('title'))->toBe($item->getTranslations('title'))
        ->and($audience->fresh()->audienceable->is($step))->toBeTrue();
});

test('knowledge progress is separate from same-id steps and never enters stages', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->create();
    $step = LearningStep::factory()->create(['id' => $item->id]);
    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => 'done'])->assertRedirect();
    $path = ResolveLearningPath::execute($user);
    expect($path->contains($item))->toBeTrue()
        ->and($path->contains($step))->toBeTrue()
        ->and($path->knowledge->sole()->stage)->toBeNull()
        ->and($path->knowledge->sole()->state)->toBe('done')
        ->and($path->steps->firstWhere('item.id', $step->id)->state)->toBe('pending');
    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => 'skipped'])->assertRedirect();
    expect(ResolveLearningPath::execute($user)->knowledge->sole()->state)->toBe('skipped');
    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => null])->assertRedirect();
    expect(ResolveLearningPath::execute($user)->knowledge->sole()->state)->toBe('pending');
    $step->update(['is_active' => false]);
    $path = ResolveLearningPath::execute($user);
    expect($path->contains($item))->toBeTrue()->and($path->contains($step))->toBeFalse();
});

test('essential and out-of-path knowledge cannot be skipped', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $essential = KnowledgeItem::factory()->audience(['importance' => LearningImportance::Essential])->create();
    $foreign = KnowledgeItem::factory()->forTenant()->create();
    asUser($user)->postJson(route('knowledgeItems.progress', $essential), ['status' => 'skipped'])->assertUnprocessable()->assertJsonValidationErrors('status');
    asUser($user)->postJson(route('knowledgeItems.progress', $foreign), ['status' => 'skipped'])->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->assertDatabaseMissing('learning_progress', ['user_id' => $user->id]);
});

test('active articles outside the path can be completed and cleared for self-learning', function (): void {
    $this->freezeSecond();
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->withoutAudiences()->create();

    asUser($user)->get(route('knowledgeItems.show', $item))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('inPath', false)->where('canTrack', true)
        ->where('item.state', 'pending')->where('item.completedAt', null)->where('item.skippable', false));
    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => 'done'])->assertRedirect();
    $this->assertDatabaseHas('learning_progress', [
        'user_id' => $user->id, 'progressable_type' => $item->getMorphClass(), 'progressable_id' => $item->id, 'status' => 'done',
    ]);
    asUser($user)->get(route('knowledgeItems.show', $item))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('inPath', false)->where('canTrack', true)
        ->where('item.state', 'done')->where('item.completedAt', now()->toIso8601String())->where('item.skippable', false));

    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => null])->assertRedirect();
    $this->assertDatabaseMissing('learning_progress', ['user_id' => $user->id, 'progressable_type' => $item->getMorphClass(), 'progressable_id' => $item->id]);
});

test('inactive articles cannot be tracked', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->withoutAudiences()->create(['is_active' => false]);

    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => 'done'])->assertForbidden();
    $this->assertDatabaseMissing('learning_progress', ['user_id' => $user->id]);
    asUser($user)->get(route('knowledgeItems.show', $item))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canTrack', false));
});

test('knowledge per-term progress reopens while lead-in completion and ordinary progress count', function (): void {
    $this->freezeTime();
    $user = makeUser(Tenant::query()->firstOrFail());
    grantPurpose($user->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepresentation);
    $user->dutiables()->firstOrFail()->update(['start_date' => today()->subDays(10), 'end_date' => today()->addYear()]);
    $term = KnowledgeItem::factory()->create(['per_term' => true]);
    $ordinary = KnowledgeItem::factory()->create();
    foreach ([$term, $ordinary] as $item) {
        $item->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => today()->subYear()]);
    }
    $items = ResolveLearningPath::execute($user)->knowledge->keyBy('item.id');
    expect($items[$term->id]->state)->toBe('pending')->and($items[$ordinary->id]->state)->toBe('done');
    $term->progress()->update(['completed_at' => today()->subDays(30)]);
    expect(ResolveLearningPath::execute($user)->knowledge->firstWhere('item.id', $term->id)->state)->toBe('done');
});

test('a source update reopens knowledge until it is read again', function (): void {
    $this->freezeTime();
    $user = makeUser(Tenant::query()->firstOrFail());
    $item = KnowledgeItem::factory()->create();
    $document = Document::factory()->create(['sharepoint_modified_at' => now()->subMinute()]);
    $item->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    $item->progress()->create(['user_id' => $user->id, 'status' => 'done', 'completed_at' => now()->subDay()]);
    expect(ResolveLearningPath::execute($user)->knowledge->sole()->state)->toBe('updated');
    asUser($user)->post(route('knowledgeItems.progress', $item), ['status' => 'done'])->assertRedirect();
    expect(ResolveLearningPath::execute($user)->knowledge->sole()->state)->toBe('done');
});

test('wiki links in a body are indexed both ways and previewed without dangling targets', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $skvc = KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'SKVC', 'en' => 'SKVC'], 'summary' => ['lt' => 'Vertina studijas.', 'en' => 'Assesses studies.']]);
    $hidden = KnowledgeItem::factory()->withoutAudiences()->create(['is_active' => false]);
    $link = fn (KnowledgeItem $target): string => '<a href="/x" data-knowledge-id="'.$target->id.'">x</a>';
    $page = KnowledgeItem::factory()->create(['description' => ['lt' => '<p>'.$link($skvc).$link($hidden).'</p>', 'en' => '<p>'.$link($skvc).'</p>']]);
    $page->update(['description' => ['lt' => '<p>'.$link($skvc).$link($hidden).$link($page).'<a data-knowledge-id="01zzzzzzzzzzzzzzzzzzzzzzzz">gone</a></p>']]);

    expect($page->outgoingLinks()->pluck('id')->sort()->values()->all())->toBe(collect([$skvc->id, $hidden->id])->sort()->values()->all())
        ->and($skvc->incomingLinks()->sole()->is($page))->toBeTrue();

    asUser($user)->get(route('knowledgeItems.show', $page))->assertOk()->assertInertia(fn (Assert $inertia) => $inertia
        ->component('Admin/Knowledge/ShowKnowledgeItem')
        ->has('linkedPages', 1)->where("linkedPages.{$skvc->id}.summary", 'Vertina studijas.')
        ->has('related', 1)->where('related.0.id', $skvc->id));
    asUser($user)->get(route('knowledgeItems.show', $skvc))->assertInertia(fn (Assert $inertia) => $inertia
        ->where('related.0.id', $page->id)->where('inPath', false));

    $page->update(['description' => ['lt' => '<p>Be nuorodų.</p>', 'en' => '']]);
    expect($page->outgoingLinks()->count())->toBe(0);
});

test('only wiki editors of the unit write articles, and audiences stay with path builders', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $other = Tenant::query()->whereKeyNot($tenant->id)->firstOrFail();
    $editor = makeUser($tenant);
    foreach (['create', 'update', 'delete'] as $ability) {
        $editor->givePermissionTo(Permission::findOrCreate("knowledgeItems.{$ability}.padalinys", 'web'));
    }
    $own = KnowledgeItem::factory()->withoutAudiences()->create(['tenant_id' => $tenant->id]);
    $foreign = KnowledgeItem::factory()->withoutAudiences()->create(['tenant_id' => $other->id]);
    $global = KnowledgeItem::factory()->withoutAudiences()->create();
    expect($editor->can('update', $own))->toBeTrue()
        ->and($editor->can('update', $foreign))->toBeFalse()
        ->and($editor->can('update', $global))->toBeFalse()
        ->and($editor->can('manage', KnowledgeItem::class))->toBeTrue();

    $article = collect(knowledgePayload($tenant->id))->except(['audiences', 'sources'])->all();
    asUser($editor)->post(route('knowledgeItems.store'), $article)->assertRedirect();
    $created = KnowledgeItem::query()->where('created_by', $editor->id)->sole();
    expect($created->tenant_id)->toBe($tenant->id)->and($created->audiences)->toBeEmpty()->and($created->sources)->toBeEmpty();

    asUser($editor)->post(route('knowledgeItems.store'), [...$article, 'tenant_id' => null])->assertForbidden();
    asUser($editor)->post(route('knowledgeItems.store'), knowledgePayload($tenant->id))->assertForbidden();
    asUser($editor)->put(route('knowledgeItems.update', $own), [...$article, 'title' => ['lt' => 'Nauja']])->assertRedirect();
    asUser($editor)->put(route('knowledgeItems.update', $foreign), $article)->assertForbidden();
    expect($own->fresh()->getTranslation('title', 'lt'))->toBe('Nauja');
});

test('articles are about institutions and types from an allowlist and show on the institution', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.update.*', 'web'));
    $institution = Institution::factory()->create();
    $type = InstitutionType::factory()->create();
    $institution->types()->attach($type);
    $direct = KnowledgeItem::factory()->withoutAudiences()->create();
    $viaType = KnowledgeItem::factory()->withoutAudiences()->create();

    asUser($user)->put(route('knowledgeItems.subjects.sync', $direct), ['subjects' => [
        ['type' => 'institution', 'id' => $institution->id], ['type' => 'institution', 'id' => $institution->id],
    ]])->assertRedirect();
    asUser($user)->put(route('knowledgeItems.subjects.sync', $viaType), ['subjects' => [['type' => 'institution_type', 'id' => (string) $type->id]]])->assertRedirect();
    asUser($user)->putJson(route('knowledgeItems.subjects.sync', $direct), ['subjects' => [['type' => 'user', 'id' => $user->id]]])
        ->assertUnprocessable()->assertJsonValidationErrors('subjects.0.type');
    asUser($user)->putJson(route('knowledgeItems.subjects.sync', $direct), ['subjects' => [['type' => 'institution', 'id' => 'missing']]])
        ->assertUnprocessable()->assertJsonValidationErrors('subjects.0.id');
    asUser(makeUser(Tenant::query()->firstOrFail()))->put(route('knowledgeItems.subjects.sync', $direct), ['subjects' => []])->assertForbidden();

    expect($direct->subjects()->count())->toBe(1);
    asUser($user)->get(route('knowledgeItems.show', $direct))->assertInertia(fn (Assert $page) => $page
        ->where('subjects.0.type', 'institution')->where('subjects.0.url', route('institutions.show', $institution)));
    asUser($user)->get(route('institutions.show', $institution))->assertInertia(fn (Assert $page) => $page
        ->missing('knowledgePages')
        ->loadDeferredProps('knowledge', fn (Assert $reload) => $reload->has('knowledgePages', 2)));
});

test('the wiki home and A–Ž index list every active article alphabetically', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Žodis', 'en' => 'Word']]);
    KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Čempionas', 'en' => 'Champion']]);
    KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Akademija', 'en' => 'Academy']]);
    KnowledgeItem::factory()->withoutAudiences()->create(['title' => ['lt' => 'Paslėpta', 'en' => 'Hidden'], 'is_active' => false]);

    asUser($user)->get(route('knowledgeItems.alphabet'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/IndexKnowledgeAlphabet')->has('index', 3)
        ->where('index.0.title', 'Akademija')->where('index.1.title', 'Čempionas')->where('index.2.title', 'Žodis'));
    asUser($user)->get(route('knowledgeBase.show'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/ShowKnowledgeBase')->has('index', 3)->has('sections')->has('knowledge.items'));
});

test('knowledge libraries and forms expose their dedicated payloads to curators', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $item = KnowledgeItem::factory()->create();
    asUser($user)->get(route('knowledgeItems.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/IndexKnowledgeItem')->has('items', 1)->has('sections')->has('audienceTypes')->has('sectionScopes')
        ->where('canCreate', true)->whereType('items.0.perTerm', 'boolean')->missing('items.0.kind'));
    asUser($user)->get(route('knowledgeItems.create'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/CreateKnowledgeItem')->has('sections')->has('tenants'));
    asUser($user)->get(route('knowledgeItems.edit', $item))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/EditKnowledgeItem')->where('item.id', $item->id)->has('item.title.lt')
        ->has('item.sources')->missing('item.kind'));
    asUser($user)->get(route('learningSteps.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('canCreate', true)->whereType('steps.0.perTerm', 'boolean')->missing('steps.0.flags')->missing('sections'));
});
