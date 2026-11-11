<?php

use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a tenant curator can create update and delete their sections but cannot target global or foreign sections', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    foreach (['create', 'update', 'delete'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.padalinys", 'web'));
    }
    $payload = ['title' => ['lt' => 'Mokymai', 'en' => 'Training'], 'tenant_id' => $tenant->id];
    asUser($user)->post(route('knowledgeSections.store'), $payload)->assertRedirect();
    $section = KnowledgeSection::query()->where('created_by', $user->id)->sole();
    expect($section->tenant_id)->toBe($tenant->id);
    asUser($user)->patch(route('knowledgeSections.update', $section), [...$payload, 'order' => 3])->assertRedirect();
    expect($section->fresh()->order)->toBe(3);
    asUser($user)->patch(route('knowledgeSections.update', $section), [...$payload, 'tenant_id' => null])->assertForbidden();
    $global = KnowledgeSection::factory()->create();
    $foreign = KnowledgeSection::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
    foreach ([$global, $foreign] as $forbidden) {
        asUser($user)->patch(route('knowledgeSections.update', $forbidden), $payload)->assertForbidden();
        asUser($user)->delete(route('knowledgeSections.destroy', $forbidden))->assertForbidden();
    }
    asUser($user)->post(route('knowledgeSections.store'), [...$payload, 'tenant_id' => null])->assertForbidden();
    asUser($user)->delete(route('knowledgeSections.destroy', $section))->assertRedirect();
    $this->assertDatabaseMissing('knowledge_sections', ['id' => $section->id]);
});

test('a global curator can manage global sections and deleting a section keeps its documents', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['create', 'update', 'delete'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.*", 'web'));
    }
    $payload = ['title' => ['lt' => 'Mokymai', 'en' => 'Training']];
    asUser($user)->post(route('knowledgeSections.store'), $payload)->assertRedirect();
    $section = KnowledgeSection::query()->where('created_by', $user->id)->sole();
    $step = KnowledgeItem::factory()->create(['knowledge_section_id' => $section->id]);
    asUser($user)->patch(route('knowledgeSections.update', $section), [...$payload, 'order' => 2])->assertRedirect();
    expect($section->fresh()->order)->toBe(2);
    asUser($user)->delete(route('knowledgeSections.destroy', $section))->assertRedirect();
    expect($step->fresh()->knowledge_section_id)->toBeNull();
});
