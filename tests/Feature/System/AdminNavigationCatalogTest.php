<?php

/**
 * The navigation catalog (O19) is the single, server-side definition of "which admin pages does
 * this user get" — see `App\Services\AdminNavigation\AdminNavigationCatalog`. These tests lock in
 * the exact shape per persona so a future permission change that silently shows or hides a
 * section is caught here rather than in a rep's face, plus a route-coverage guard so a new
 * `/mano` index route cannot be forgotten.
 *
 * @see AGENTS.md — "Every mutating route authorizes" (this catalog only ever hides GET routes;
 *      it grants nothing a controller does not already allow)
 */

use App\Models\Tenant;
use App\Models\User;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

pest()->use(RefreshDatabase::class);

/**
 * Bare-name GET routes with no `.index` suffix that are still a workspace/section entry point,
 * so the coverage guard must check them exactly like an `.index` route.
 */
const SECTION_LANDING_ROUTES = [
    'dashboard', 'dashboard.atstovavimas', 'dashboard.reservations', 'dashboard.svetaine',
    'userTasks', 'institutionGraph', 'dutiables.timeline', 'tasks.summary', 'systemStatus',
    'mailQueue', 'administration', 'duties.updateUsersWizard', 'profile',
];

/**
 * Routes deliberately left out of the catalog, with the reason review should hold it to.
 */
const EXCLUDED_FROM_CATALOG = [
    'administration' => 'the workspace picker panel doubles as Visi skyriai (O25); PR 7.5 re-homes this page itself',
    'profile' => 'reached from the account menu, not a workspace section (PR 4.4)',
    'mySupportRequests.index' => 'Pagalba entry point, not a workspace section (PR 4.4)',
    'push-subscription.index' => 'device push-subscription settings, no navigational destination',
    'settings.cadences.index' => 'reached from within Nustatymai, not a top-level section',
];

/**
 * @return array<string, list<string>> workspace key => ordered section keys
 */
function catalogSummary(AdminNavigationCatalog $catalog, User $user): array
{
    return collect($catalog->for($user)['workspaces'])
        ->mapWithKeys(fn (array $workspace) => [
            $workspace['key'] => collect($workspace['sections'])->pluck('key')->all(),
        ])
        ->all();
}

/**
 * @return list<array{routeName: string, routeParams: array<string, mixed>}>
 */
function catalogVisitableSections(AdminNavigationCatalog $catalog, User $user): array
{
    return collect($catalog->for($user)['workspaces'])
        ->flatMap(fn (array $workspace) => $workspace['sections'])
        ->map(fn (array $section) => ['routeName' => $section['routeName'], 'routeParams' => $section['routeParams']])
        ->unique(fn (array $section) => $section['routeName'].json_encode($section['routeParams']))
        ->values()
        ->all();
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->catalog = app(AdminNavigationCatalog::class);
});

describe('per-persona visibility', function () {
    test('a plain member sees only Pradžia and the always-visible reservation entries', function (): void {
        $user = makeUser($this->tenant);

        // Rezervacijos' Apžvalga is deliberately "always" (rules/navigation.md), and both
        // ResourcePolicy::viewAny() and ReservationPolicy::create() are unconditional `true` —
        // "anyone can view the resource listing" / request a reservation — so a member with no
        // role at all still gets a foothold in Rezervacijos. This is existing, real behaviour,
        // not something this catalog introduces.
        expect(catalogSummary($this->catalog, $user))->toEqual([
            'pradzia' => ['apzvalga', 'uzduotys', 'pranesimai'],
            'rezervacijos' => ['apzvalga', 'istekliai'],
        ]);

        $payload = $this->catalog->for($user);
        expect($payload['workspaces'][0])->toEqual([
            'key' => 'pradzia',
            'label' => 'shell.workspaces.pradzia.title',
            'description' => 'shell.workspaces.pradzia.description',
            'sections' => [
                ['key' => 'apzvalga', 'label' => 'shell.sections.apzvalga', 'routeName' => 'dashboard', 'routeParams' => [], 'entityType' => null, 'collectionActions' => []],
                ['key' => 'uzduotys', 'label' => 'shell.sections.uzduotys', 'routeName' => 'userTasks', 'routeParams' => [], 'entityType' => 'task', 'collectionActions' => []],
                ['key' => 'pranesimai', 'label' => 'shell.sections.pranesimai', 'routeName' => 'notifications.index', 'routeParams' => [], 'entityType' => null, 'collectionActions' => []],
            ],
            'createActions' => [],
        ]);
        expect($payload['workspaces'][1]['createActions'])->toEqual([
            ['key' => 'new_reservation', 'label' => 'shell.actions.new_reservation.title', 'description' => 'shell.actions.new_reservation.description', 'entityType' => 'reservation', 'target' => ['kind' => 'route', 'routeName' => 'reservations.create']],
        ]);
    });

    test('a plain Student Representative sees no Sistema or Organizacija workspace', function (): void {
        $user = makeTenantUserWithRole('Student Representative', $this->tenant);

        // MeetingPolicy has no viewAny() override, so it falls back to HasCommonChecks'
        // `meetings.read.padalinys` — a permission this role never gets (it only holds
        // `meetings.read.own`). A rep who has not yet been handed the padalinys-wide read scope
        // cannot see Posėdžiai, the ViSAK overview, or Darbotvarkės klausimai, even though they
        // can create and edit their own meetings. Real, current behaviour — not introduced here.
        expect(catalogSummary($this->catalog, $user))->toEqual([
            'pradzia' => ['apzvalga', 'uzduotys', 'pranesimai'],
            'atstovavimas' => ['institucijos', 'problemos', 'institucijos_grafas'],
            'rezervacijos' => ['apzvalga', 'istekliai'],
        ]);
    });

    test('a Student Representative who is also a Communication Coordinator gains Organizacija and Svetainė', function (): void {
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();
        $duty->pivot->end_date = null;
        $duty->pivot->save();
        $duty->assignRole('Student Representative');
        $duty->assignRole('Communication Coordinator');

        expect(catalogSummary($this->catalog, $user))->toEqual([
            'pradzia' => ['apzvalga', 'uzduotys', 'pranesimai'],
            'atstovavimas' => ['apzvalga', 'institucijos', 'posedziai', 'darbotvarkes_klausimai', 'problemos', 'pareigybiu_laikotarpiai', 'institucijos_grafas'],
            'rezervacijos' => ['apzvalga', 'istekliai'],
            'svetaine' => ['apzvalga', 'puslapiai', 'naujienos', 'kalendorius', 'baneriai', 'greitosios_nuorodos', 'failai'],
            'organizacija' => ['nariai', 'pareigybes', 'pareigybiu_atnaujinimas', 'studiju_programos', 'formos'],
        ]);
    });

    test('a resources administrator sees Rezervacijos plus the task summary, but no Institucijos', function (): void {
        $user = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);

        // `tasks.read.padalinys` is part of this role's permission set — TaskPolicy::viewAny()
        // checks exactly that string, so this role incidentally sees ViSAK's Užduočių suvestinė,
        // despite holding none of the meeting/institution permissions the workspace is named
        // for. Genuine, if surprising: worth a product conversation, not a bug this PR should
        // paper over by inventing a narrower gate the controller does not itself enforce.
        expect(catalogSummary($this->catalog, $user))->toEqual([
            'pradzia' => ['apzvalga', 'uzduotys', 'pranesimai'],
            'atstovavimas' => ['uzduociu_suvestine'],
            'rezervacijos' => ['apzvalga', 'rezervacijos', 'istekliai', 'kategorijos'],
            'svetaine' => ['dokumentai'],
        ]);
    });

    test('a super admin sees every workspace and every section', function (): void {
        $user = makeAdminUser($this->tenant);

        expect(catalogSummary($this->catalog, $user))->toEqual([
            'pradzia' => ['apzvalga', 'uzduotys', 'pranesimai'],
            'atstovavimas' => ['apzvalga', 'institucijos', 'posedziai', 'darbotvarkes_klausimai', 'problemos', 'pareigybiu_laikotarpiai', 'uzduociu_suvestine', 'institucijos_grafas'],
            'rezervacijos' => ['apzvalga', 'rezervacijos', 'istekliai', 'kategorijos'],
            'svetaine' => ['apzvalga', 'puslapiai', 'naujienos', 'kalendorius', 'baneriai', 'navigacija', 'greitosios_nuorodos', 'renginiu_tipai', 'zymos', 'failai', 'dokumentai', 'studiju_rinkiniai'],
            'organizacija' => ['nariai', 'pareigybes', 'pareigybiu_atnaujinimas', 'padaliniai', 'studiju_programos', 'formos'],
            'sistema' => ['roles', 'leidimai', 'tipai', 'rysiai', 'nustatymai', 'sistemos_busena', 'laisku_eile', 'pagalbos_uzklausos', 'sharepoint_failai'],
        ]);
    });

    test('an empty workspace does not render at all', function (): void {
        // A plain member has zero visible sections in ViSAK, Svetainė, Organizacija and Sistema —
        // none of those keys should appear in the payload, rather than an empty `sections: []`.
        $user = makeUser($this->tenant);

        $keys = collect($this->catalog->for($user)['workspaces'])->pluck('key');

        expect($keys)->not->toContain('atstovavimas', 'svetaine', 'organizacija', 'sistema');
    });
});

describe('route-coverage guard', function () {
    test('every admin index or landing route is in the catalog or excluded', function (): void {
        $indexRouteNames = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true))
            ->filter(fn ($route) => str_starts_with($route->uri(), 'mano'))
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->filter(fn (string $name) => str_ends_with($name, '.index') || in_array($name, SECTION_LANDING_ROUTES, true))
            ->unique()
            ->values();

        $catalogRouteNames = collect(catalogVisitableSections($this->catalog, makeAdminUser($this->tenant)))
            ->pluck('routeName')
            ->unique();

        $missing = $indexRouteNames
            ->reject(fn (string $name) => $catalogRouteNames->contains($name))
            ->reject(fn (string $name) => array_key_exists($name, EXCLUDED_FROM_CATALOG))
            ->values()
            ->all();

        expect($missing)->toBeEmpty();
    });

    test('every catalog route name is a real route', function (): void {
        $realNames = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->unique();

        $catalogRouteNames = collect(catalogVisitableSections($this->catalog, makeAdminUser($this->tenant)))
            ->pluck('routeName')
            ->unique();

        $unknown = $catalogRouteNames->diff($realNames)->values()->all();

        expect($unknown)->toBeEmpty();
    });
});

describe('access parity', function () {
    /**
     * "Hidden, never disabled" only holds if a catalog gate never disagrees with the route's
     * own authorization — otherwise a section shows up and then 403s the moment it is opened.
     */
    test('every section a persona sees actually opens for them', function (string $factory) {
        /** @var User $user */
        $user = match ($factory) {
            'plain' => makeUser($this->tenant),
            'rep' => makeTenantUserWithRole('Student Representative', $this->tenant),
            'resources' => makeTenantUserWithRole('Išteklių administratorius', $this->tenant),
            'superAdmin' => makeAdminUser($this->tenant),
        };

        foreach (catalogVisitableSections($this->catalog, $user) as $section) {
            $response = asUser($user)->get(route($section['routeName'], $section['routeParams']));

            expect($response->status())
                ->not->toBe(403, "{$section['routeName']} 403'd for persona [{$factory}] despite being catalog-visible");
        }
    })->with(['plain', 'rep', 'resources', 'superAdmin']);
});

describe('caching', function () {
    test('the resolved catalog is served from cache on a second call', function (): void {
        $user = makeUser($this->tenant);

        $this->catalog->for($user);

        // Prove the second call reads the cache rather than recomputing: overwrite the cached
        // value directly and confirm `for()` returns the overwritten value.
        Cache::put(AdminNavigationCatalog::CACHE_PREFIX.$user->id, ['workspaces' => ['sentinel']], 1800);

        expect($this->catalog->for($user))->toBe(['workspaces' => ['sentinel']]);
    });

    test('forgetCachedMaps invalidates the cached catalog', function (): void {
        $user = makeUser($this->tenant);
        $duty = $user->duties()->first();
        $duty->pivot->end_date = null;
        $duty->pivot->save();

        expect(catalogSummary($this->catalog, $user))->not->toHaveKey('organizacija');

        $duty->assignRole('Communication Coordinator');

        // Assigning a role straight to a duty (the test fixture's shortcut, not a flow the app
        // itself exposes) invalidates neither cache on its own: `PermissionMapBuilder`'s own
        // maps via `forgetCachedMaps()`, and separately `ModelAuthorizer`'s persisted
        // `auth:duties:{id}` cache (see `.ai/rules/services.md`) via `resetCache()` — the same
        // pair `HandleDutiableChange` calls together for the flow the app does expose.
        PermissionMapBuilder::forgetCachedMaps($user->id);
        app(ModelAuthorizer::class)->resetCache($user->id);

        expect(catalogSummary($this->catalog, $user->fresh()))->toHaveKey('organizacija');
    });
});
