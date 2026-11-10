<?php

use App\Models\LearningSection;
use App\Models\LearningStep;
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
    asUser($user)->post(route('learningSections.store'), $payload)->assertRedirect();
    $section = LearningSection::query()->where('created_by', $user->id)->sole();
    expect($section->tenant_id)->toBe($tenant->id);
    asUser($user)->patch(route('learningSections.update', $section), [...$payload, 'order' => 3])->assertRedirect();
    expect($section->fresh()->order)->toBe(3);
    asUser($user)->patch(route('learningSections.update', $section), [...$payload, 'tenant_id' => null])->assertForbidden();
    $global = LearningSection::factory()->create();
    $foreign = LearningSection::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
    foreach ([$global, $foreign] as $forbidden) {
        asUser($user)->patch(route('learningSections.update', $forbidden), $payload)->assertForbidden();
        asUser($user)->delete(route('learningSections.destroy', $forbidden))->assertForbidden();
    }
    asUser($user)->post(route('learningSections.store'), [...$payload, 'tenant_id' => null])->assertForbidden();
    asUser($user)->delete(route('learningSections.destroy', $section))->assertRedirect();
    $this->assertDatabaseMissing('learning_sections', ['id' => $section->id]);
});

test('a global curator can manage global sections and deleting a section keeps its documents', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    foreach (['create', 'update', 'delete'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate("learningSteps.{$ability}.*", 'web'));
    }
    $payload = ['title' => ['lt' => 'Mokymai', 'en' => 'Training']];
    asUser($user)->post(route('learningSections.store'), $payload)->assertRedirect();
    $section = LearningSection::query()->where('created_by', $user->id)->sole();
    $step = LearningStep::factory()->create(['learning_section_id' => $section->id]);
    asUser($user)->patch(route('learningSections.update', $section), [...$payload, 'order' => 2])->assertRedirect();
    expect($section->fresh()->order)->toBe(2);
    asUser($user)->delete(route('learningSections.destroy', $section))->assertRedirect();
    expect($step->fresh()->learning_section_id)->toBeNull();
});
