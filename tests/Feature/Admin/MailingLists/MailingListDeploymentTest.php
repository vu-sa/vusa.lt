<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

test('upgrading existing coordinator roles grants mailing list access without replacing their permissions', function (): void {
    $tenant = Tenant::main();
    $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $tenant);
    $central = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $tenant);
    $role = Role::findByName('Studentų atstovų koordinatorius');
    $unrelated = $role->permissions()->where('name', 'not like', 'mailingLists.%')->pluck('name')->all();
    $migration = require database_path('migrations/2026_10_16_162000_harden_mailing_list_archives_and_permissions.php');
    $migration->down();
    expect($role->fresh()->hasPermissionTo('mailingLists.create.padalinys'))->toBeFalse();
    $authorizer = app(ModelAuthorizer::class);
    expect($authorizer->scope($coordinator, 'mailingLists.read.padalinys')->granted)->toBeFalse();

    $migration->up();

    expect(Schema::hasColumn('mailing_lists', 'archive_backfill_offset'))->toBeTrue()
        ->and($authorizer->scope($coordinator, 'mailingLists.read.padalinys')->granted)->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('mailingLists.create.padalinys'))->toBeTrue()
        ->and($role->fresh()->permissions->pluck('name')->intersect($unrelated)->count())->toBe(count($unrelated));
    asUser($coordinator)->get(route('mailingLists.index'))->assertOk();
    asUser($central)->get(route('mailingLists.index'))->assertOk();
});
