<?php

use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a tenant curator can only create and edit tenant steps in their own unit', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.padalinys', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.padalinys', 'web'));
    $own = LearningStep::factory()->forTenant($tenant)->create();
    $other = LearningStep::factory()->forTenant()->create();
    $global = LearningStep::factory()->create();
    expect($user->can('create', LearningStep::class))->toBeTrue();
    expect($user->can('update', $own))->toBeTrue();
    expect($user->can('update', $other))->toBeFalse();
    expect($user->can('update', $global))->toBeFalse();
    $payload = ['kind' => 'action', 'audiences' => [['type' => 'everyone', 'tenant_id' => $other->audiences->first()->tenant_id,
        'importance' => 'recommended', 'stage' => 'before_start']], 'title' => ['lt' => 'Žingsnis', 'en' => 'Step']];
    asUser($user)->post(route('learningSteps.store'), $payload)->assertForbidden();
    $payload['audiences'][0]['tenant_id'] = null;
    asUser($user)->post(route('learningSteps.store'), $payload)->assertForbidden();
    $payload['audiences'][0]['tenant_id'] = $tenant->id;
    asUser($user)->post(route('learningSteps.store'), $payload)->assertRedirect();
    $created = LearningStep::query()->where('created_by', $user->id)->sole();
    expect($created->audiences->sole()->tenant_id)->toBe($tenant->id);
});

test('global curators edit any audience but nobody deletes system steps', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.delete.*', 'web'));
    $step = LearningStep::factory()->forTenant()->create();
    $system = LearningStep::query()->where('key', 'install_app')->firstOrFail();
    expect($user->can('update', $step))->toBeTrue();
    expect($user->can('delete', $system))->toBeFalse();
    $admin = makeAdminUser(Tenant::query()->firstOrFail());
    asUser($admin)->delete(route('learningSteps.destroy', $system))->assertForbidden();
    $this->assertDatabaseHas('learning_steps', ['id' => $system->id]);
});
