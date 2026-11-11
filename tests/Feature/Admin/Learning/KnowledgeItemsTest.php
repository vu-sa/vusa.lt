<?php

use App\Actions\ResolveLearningPath;
use App\Enums\LearningImportance;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\GlossaryTerm;
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

test('essential and out-of-path knowledge cannot be skipped or completed by outsiders', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $essential = KnowledgeItem::factory()->audience(['importance' => LearningImportance::Essential])->create();
    $foreign = KnowledgeItem::factory()->forTenant()->create();
    asUser($user)->postJson(route('knowledgeItems.progress', $essential), ['status' => 'skipped'])->assertUnprocessable()->assertJsonValidationErrors('status');
    asUser($user)->post(route('knowledgeItems.progress', $foreign), ['status' => 'done'])->assertForbidden();
    $this->assertDatabaseMissing('learning_progress', ['user_id' => $user->id]);
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

test('glossary lists and term pages prioritize the viewers path then section and item order', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $early = KnowledgeSection::factory()->create(['order' => 1]);
    $late = KnowledgeSection::factory()->create(['order' => 2]);
    $recommended = KnowledgeItem::factory()->create(['knowledge_section_id' => $early->id]);
    $essential = KnowledgeItem::factory()->audience(['importance' => 'essential'])->create(['knowledge_section_id' => $late->id, 'order' => 2]);
    $firstEssential = KnowledgeItem::factory()->audience(['importance' => 'essential'])->create(['knowledge_section_id' => $early->id, 'order' => 3]);
    $foreign = KnowledgeItem::factory()->forTenant()->create(['knowledge_section_id' => $early->id]);
    $term = GlossaryTerm::factory()->create(['term' => ['lt' => 'Bendra sąvoka', 'en' => 'Shared term']]);
    $term->knowledgeItems()->attach([$recommended->id, $essential->id, $firstEssential->id, $foreign->id]);
    $lessRelevant = GlossaryTerm::factory()->create(['term' => ['lt' => 'A sąvoka', 'en' => 'A term']]);
    $lessRelevant->knowledgeItems()->attach($recommended);
    $outside = GlossaryTerm::factory()->create();
    $outside->knowledgeItems()->attach($foreign);

    asUser($user)->get(route('glossaryTerms.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/IndexGlossaryTerm')->where('terms.0.id', $term->id)
        ->where('terms.0.priority.importance', 'essential')->where('terms.1.id', $lessRelevant->id)->where('terms.2.id', $outside->id));
    asUser($user)->get(route('glossaryTerms.show', $term))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Knowledge/ShowGlossaryTerm')->where('term.id', $term->id)
        ->where('term.items.0.id', $firstEssential->id)->where('term.items.1.id', $essential->id)
        ->where('term.items.2.id', $recommended->id)->where('term.items.3.id', $foreign->id)
        ->where('term.items.0.inPath', true)->where('term.items.0.importance', 'essential')
        ->where('term.items.3.inPath', false)->where('term.items.3.importance', null)
        ->where('term.items.0.url', route('knowledgeItems.show', $firstEssential))
        ->whereType('term.items.0.state', 'string')->has('term.translations.term.en'));
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
