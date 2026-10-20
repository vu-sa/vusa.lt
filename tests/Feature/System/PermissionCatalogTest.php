<?php

use App\Policies\ModelPolicy;
use App\Support\Permissions\BaselineAccess;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Checked by SupportRequestPolicy on purpose: nobody but super admins may hold it. */
const UNSEEDED_RESOURCES = ['supportRequests'];

test('every policy checks a resource the catalog offers', function (): void {
    $resources = array_keys(PermissionCatalog::abilities());

    collect(File::files(app_path('Policies')))
        ->map(fn ($file): string => 'App\\Policies\\'.$file->getFilenameWithoutExtension())
        ->filter(fn (string $class): bool => is_subclass_of($class, ModelPolicy::class))
        ->map(fn (string $class): ?string => app($class)->resourceName())
        ->filter()
        ->each(fn (string $resource) => expect($resources)->toContain($resource));
});

test('every permission named in the app is grantable or part of the baseline', function (): void {
    $names = collect(File::allFiles(app_path()))
        ->flatMap(function ($file): array {
            preg_match_all("/'([a-zA-Z]+\\.(?:create|read|update|delete|forceDelete)\\.(?:own|padalinys|\\*))'/", $file->getContents(), $matches);

            return $matches[1];
        })
        ->unique()
        ->reject(fn (string $name): bool => in_array(Str::before($name, '.'), UNSEEDED_RESOURCES, true));

    $names->each(fn (string $name) => expect(PermissionCatalog::isGrantable($name) || BaselineAccess::isRetired($name))
        ->toBeTrue("{$name} is checked in app/ but no responsibility can hold it"));
});

test('the catalog offers the resources the old hand-kept list forgot', function (): void {
    expect(array_keys(PermissionCatalog::abilities()))->toContain('mailingLists', 'goals', 'studySets', 'eventTypes', 'settings');
});

test('baseline permissions are never grantable', function (): void {
    foreach (BaselineAccess::retiredPermissionNames() as $name) {
        expect(PermissionCatalog::isGrantable($name))->toBeFalse();
    }
});

test('every resource belongs to a known area', function (): void {
    foreach (PermissionCatalog::toFrontend()['resources'] as $resource) {
        expect(PermissionCatalog::AREAS)->toContain($resource['area']);
    }
});
