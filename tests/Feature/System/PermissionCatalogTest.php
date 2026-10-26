<?php

use App\Models\Duty;
use App\Policies\ModelPolicy;
use App\Support\Permissions\BaselineAccess;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Checked by SupportRequestPolicy on purpose: nobody but super admins may hold it. */
const UNSEEDED_RESOURCES = ['supportRequests'];

/** Tenants are for super admins only (TenantPolicy). */
const POLICIES_OUTSIDE_THE_CATALOG = ['tenants'];

test('every policy checks a resource the catalog offers', function (): void {
    $resources = [...array_keys(PermissionCatalog::abilities()), ...POLICIES_OUTSIDE_THE_CATALOG];

    collect(File::files(app_path('Policies')))
        ->map(fn ($file): string => 'App\\Policies\\'.$file->getFilenameWithoutExtension())
        ->filter(fn (string $class): bool => is_subclass_of($class, ModelPolicy::class))
        ->map(fn (string $class): ?string => app($class)->resourceName())
        ->filter()
        ->each(fn (string $resource) => expect($resources)->toContain($resource));
});

test('every permission named in the app is grantable or part of the baseline', function (): void {
    // The catalog and the baseline list the very names they exclude.
    $names = collect(File::allFiles(app_path()))
        ->reject(fn ($file): bool => str_contains($file->getPathname(), 'Support/Permissions'))
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

test('combinations that grant nothing are not offered', function (string $name): void {
    expect(PermissionCatalog::isGrantable($name))->toBeFalse();
})->with([
    'agenda items follow their meeting' => 'agendaItems.update.own',
    'tasks belong to the assignee' => 'tasks.update.own',
    'users manage themselves' => 'users.read.own',
    'everyone sees their reservations' => 'reservations.read.own',
    'no create at own outside meetings and documents' => 'news.create.own',
    'no duty relation behind own' => 'files.read.own',
    'members read their institutions' => 'institutions.read.own',
    'members already read their meetings' => 'meetings.read.own',
    'permissions are only listed' => 'permissions.update.*',
    'tenants are super-admin only' => 'tenants.read.*',
]);

test('what is still offered at own', function (string $name): void {
    expect(PermissionCatalog::isGrantable($name))->toBeTrue();
})->with(['meetings.create.own', 'meetings.update.own', 'documents.create.own', 'documents.update.own', 'duties.update.own', 'goals.update.own', 'reservations.delete.own', 'settings.read.*']);

test('every own scope has a same-named relation on Duty, or is reserved on purpose', function (): void {
    $reserved = ['goals'];

    $withOwn = collect(PermissionCatalog::names())
        ->filter(fn (string $name): bool => str_ends_with($name, '.own'))
        ->map(fn (string $name): string => Str::before($name, '.'))
        ->unique()
        ->reject(fn (string $resource): bool => in_array($resource, $reserved, true) || $resource === 'duties');

    $withOwn->each(fn (string $resource) => expect(method_exists(Duty::class, $resource))
        ->toBeTrue("{$resource} offers an own scope but Duty has no {$resource}() relation to resolve it through"));
});

test('meeting scope explanations and highlighted duty dates are translated without HTML', function (string $locale): void {
    app()->setLocale($locale);
    $meeting = collect(PermissionCatalog::toFrontend()['resources'])->firstWhere('key', 'meetings');

    expect(implode('', array_column($meeting['baselineSegments'], 'text')))->toBe($meeting['baseline'])
        ->and($meeting['baselineSegments'][1]['strong'])->toBeTrue()
        ->and($meeting['scopeLabels']['own'])->not->toStartWith('access.')
        ->and($meeting['scopeDescriptions']['read']['padalinys'])->not->toStartWith('access.')
        ->and($meeting['abilities']['read'])->not->toContain('own');
})->with(['lt', 'en']);

test('rows follow the order of the admin navigation within their workspace', function (): void {
    $keys = collect(PermissionCatalog::toFrontend()['resources'])->pluck('key');

    expect($keys->first())->toBe('tasks')
        ->and($keys->search('institutions'))->toBeLessThan($keys->search('meetings'))
        ->and($keys->search('meetings'))->toBeLessThan($keys->search('goals'))
        ->and($keys->search('users'))->toBeLessThan($keys->search('duties'))
        ->and($keys->last())->toBe('settings');
});

test('the editor is told which resources have a trash', function (): void {
    $resources = collect(PermissionCatalog::toFrontend()['resources'])->keyBy('key');

    expect($resources['news']['softDeletes'])->toBeTrue()
        ->and($resources['mailingLists']['softDeletes'])->toBeFalse();
});
