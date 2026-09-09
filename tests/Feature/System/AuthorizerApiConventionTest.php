<?php

/**
 * ModelAuthorizer API convention test
 *
 * The authorizer used to be a request singleton whose public surface was mutable state
 * left behind by the previous caller: `getTenants()` with no argument resolved against
 * `lastCheckedPermission`, and `$isAllScope`/`$permissableDuties` were whatever the last
 * check had set. Because `checkAllRoleables()` never reset `$isAllScope`, the first
 * `*`-scoped permission in a request latched it true for every later resolution — a
 * Resource Manager holding `resources.read.*` gained all-tenant reservation approval, and
 * a Global Communication Coordinator holding `tags.update.*` gained all-tenant everything.
 *
 * The API now takes `(User, permission)` on every call and returns an immutable
 * `PermissionScope`. This test keeps the ambient-state surface from coming back: the old
 * method and property names must not reappear anywhere under `app/`.
 *
 * @see ModelAuthorizer
 * @see PermissionScope
 */

use App\Services\Authorization\PermissionScope;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

/**
 * Removed members of the authorizer's API. Each one made the result of a call depend on
 * what some other caller did earlier in the request.
 *
 * @var array<string, string>
 */
const REMOVED_AUTHORIZER_API = [
    '->forUser(' => 'the authorizer has no current user; pass the User to scope()/allows()/tenants()/duties()',
    '->checkAllRoleables(' => 'use allows($user, $permission)',
    '->getTenants(' => 'use tenants($user, $permission), or scope($user, $permission)->tenants',
    '->getPermissableDuties(' => 'use duties($user, $permission), or scope($user, $permission)->duties',
    '->permissableDuties' => 'use scope($user, $permission)->duties',
];

/**
 * Files allowed to keep a member name. `PermissionScope` legitimately owns `isAllScope`
 * as a readonly property of one resolution, so `->isAllScope` is checked separately.
 *
 * @var array<int, string>
 */
const AUTHORIZER_API_EXEMPTIONS = [
    'app/Services/Authorization/PermissionScope.php',
];

test('app code never reaches for the authorizer ambient-state API', function (): void {
    $offenders = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relative = Str::after($file->getPathname(), base_path().'/');

        if (in_array($relative, AUTHORIZER_API_EXEMPTIONS, true)) {
            continue;
        }

        // Strip comments: prose describing the removed API is not an offence.
        $contents = (string) php_strip_whitespace($file->getPathname());

        foreach (REMOVED_AUTHORIZER_API as $needle => $replacement) {
            if (str_contains($contents, $needle)) {
                $offenders[] = "{$relative} uses {$needle} — {$replacement}";
            }
        }
    }

    expect($offenders)->toBeEmpty(
        "The ModelAuthorizer ambient-state API is gone. Offending call sites:\n".implode("\n", $offenders)
    );
});

test('the authorizer exposes no mutable public state', function (): void {
    $reflection = new ReflectionClass(ModelAuthorizer::class);

    expect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))->toBeEmpty();
});

test('every public authorizer method takes the user explicitly', function (): void {
    $reflection = new ReflectionClass(ModelAuthorizer::class);

    $withoutUser = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $method) => $method->isStatic() || $method->isConstructor())
        // resetCache takes a User|int|string, so it is user-addressed too.
        ->reject(fn (ReflectionMethod $method) => $method->getNumberOfParameters() > 0
            && in_array($method->getParameters()[0]->getName(), ['user'], true))
        ->map(fn (ReflectionMethod $method) => $method->getName())
        ->all();

    expect($withoutUser)->toBeEmpty();
});
