<?php

use App\Models\Permission;
use App\Services\Typesense\TypesenseCollectionConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * TypesenseScopedKeyService drops a collection from the user's scoped key when the configured
 * permission check fails. A permission string that is simply misspelled therefore fails
 * silently — the search box just returns nothing for everyone but super admins. That is how
 * `calendar.read.padalinys` (singular) went unnoticed while every seeded permission is plural.
 */
test('every configured admin collection permission is a real, seeded permission', function (): void {
    $configured = collect(TypesenseCollectionConfig::getAdminCollections())
        ->flatMap(fn (array $config) => [$config['permission'] ?? null, $config['own_permission'] ?? null])
        ->filter()
        ->unique()
        ->values();

    expect($configured)->not->toBeEmpty();

    $missing = $configured
        ->reject(fn (string $permission) => Permission::query()->where('name', $permission)->exists())
        ->values()
        ->all();

    expect($missing)->toBeEmpty();
});
