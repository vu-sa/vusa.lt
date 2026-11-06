<?php

use App\Models\Calendar;
use App\Models\News;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('duplication requires permission to view the source', function (string $model, string $resource, string $routeName): void {
    $tenant = Tenant::query()->first();
    $actor = makeUser($tenant);
    $role = Role::factory()->create();
    $role->givePermissionTo($resource.'.create.padalinys');
    $actor->duties()->first()->assignRole($role);
    $source = $model::factory()->for($tenant)->create();
    $before = $model::count();

    asUser($actor)->postJson(route($routeName, $source))->assertForbidden();

    expect($model::count())->toBe($before);
})->with([
    'news' => [News::class, 'news', 'news.duplicate'],
    'calendar' => [Calendar::class, 'calendars', 'calendar.duplicate'],
]);

test('duplication requires creation access in the source tenant even with global read access', function (string $model, string $resource, string $routeName): void {
    $tenant = Tenant::query()->first();
    $actor = makeUser($tenant);
    $role = Role::factory()->create();
    $role->givePermissionTo([$resource.'.create.padalinys', $resource.'.read.*']);
    $actor->duties()->first()->assignRole($role);
    $source = $model::factory()->for(Tenant::query()->whereKeyNot($tenant->id)->first())->create();
    $before = $model::count();

    asUser($actor)->postJson(route($routeName, $source))->assertForbidden();

    expect($model::count())->toBe($before);
})->with([
    'news' => [News::class, 'news', 'news.duplicate'],
    'calendar' => [Calendar::class, 'calendars', 'calendar.duplicate'],
]);

test('duplication preserves the source tenant when both permissions allow it', function (string $model, string $resource, string $routeName): void {
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $source = $model::factory()->for($tenant)->create();
    $before = $model::count();

    asUser($actor)->postJson(route($routeName, $source))->assertRedirect();

    expect($model::count())->toBe($before + 1);
    expect($model::query()->whereKeyNot($source->id)->where('tenant_id', $tenant->id)->exists())->toBeTrue();
})->with([
    'news' => [News::class, 'news', 'news.duplicate'],
    'calendar' => [Calendar::class, 'calendars', 'calendar.duplicate'],
]);
