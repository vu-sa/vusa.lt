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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
    'profile.roles' => 'Mano rolės ir pareigybės, reached from the account menu and every 403 page (PR 5.9)',
    'mySupportRequests.index' => 'Pagalba entry point, not a workspace section (PR 4.4)',
    'push-subscription.index' => 'device push-subscription settings, no navigational destination',
    'settings.cadences.index' => 'reached from within Nustatymai, not a top-level section',
];

/**
 * GET `/mano` routes that deliberately belong to no workspace: they are reached from the account
 * menu or Pagalba, or are legacy redirects that never render (`SearchController`'s own docblocks).
 */
const WORKSPACELESS_ROUTES = [
    'administration', 'profile', 'profile.roles', 'approvals.history', 'mySupportRequests.index', 'mySupportRequests.create',
    'push-subscription.index', 'search.index', 'search.agendaItems', 'search.institutions',
    'search.meetings', 'search.resources',
];

/**
 * Every section that claims a route, best match first: a section carrying `routeParams` only
 * claims the route when the params agree, and an exact name beats a wildcard. The frontend's
 * `resolveActive()` (useAdminNavigation.ts) implements the same order.
 *
 * @param  list<array<string, mixed>>  $workspaces
 * @param  array<string, mixed>  $params
 * @return list<array{workspace: string, section: string}>
 */
function catalogCandidates(array $workspaces, string $routeName, array $params = []): array
{
    $candidates = [];

    foreach ($workspaces as $workspace) {
        foreach ($workspace['sections'] as $section) {
            $pattern = collect($section['matches'])->first(fn (string $pattern) => Str::is($pattern, $routeName));

            $paramsAgree = collect($section['routeParams'])
                ->every(fn ($value, $key) => (string) ($params[$key] ?? '') === (string) $value);

            if ($pattern === null || ! $paramsAgree) {
                continue;
            }

            $candidates[] = [
                'workspace' => $workspace['key'],
                'section' => $section['key'],
                'withParams' => $section['routeParams'] !== [],
                'exact' => $pattern === $routeName,
            ];
        }
    }

    return collect($candidates)
        ->sortBy([['withParams', 'desc'], ['exact', 'desc']])
        ->map(fn (array $candidate) => ['workspace' => $candidate['workspace'], 'section' => $candidate['section']])
        ->values()
        ->all();
}

/**
 * @return Collection<int, string>
 */
function adminGetRouteNames(): Collection
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->filter(fn ($route) => str_starts_with($route->uri(), 'mano'))
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->unique()
        ->values();
}

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
                ['key' => 'apzvalga', 'label' => 'shell.sections.apzvalga', 'routeName' => 'dashboard', 'routeParams' => [], 'entityType' => null, 'collectionActions' => [], 'matches' => ['dashboard']],
                ['key' => 'uzduotys', 'label' => 'shell.sections.uzduotys', 'routeName' => 'userTasks', 'routeParams' => [], 'entityType' => 'task', 'collectionActions' => [], 'matches' => ['userTasks']],
                ['key' => 'pranesimai', 'label' => 'shell.sections.pranesimai', 'routeName' => 'notifications.index', 'routeParams' => [], 'entityType' => null, 'collectionActions' => [], 'matches' => ['notifications.*']],
            ],
            'createActions' => [],
        ]);
        expect($payload['workspaces'][1]['createActions'])->toEqual([
            ['key' => 'new_reservation', 'label' => 'shell.actions.new_reservation.title', 'description' => 'shell.actions.new_reservation.description', 'entityType' => 'reservation', 'target' => ['kind' => 'route', 'routeName' => 'reservations.create']],
        ]);
    });

    test('a plain Student Representative sees no Sistema or Organizacija workspace', function (): void {
        $user = makeTenantUserWithRole('Student Representative', $this->tenant);

        // MeetingPolicy::viewAny() accepts `meetings.read.own`, which is all this role holds, so
        // the rep sees Posėdžiai, the ViSAK overview and Darbotvarkės klausimai. Which rows they
        // get is the scoped search key's job (own_permission), not the catalog's.
        expect(catalogSummary($this->catalog, $user))->toEqual([
            'pradzia' => ['apzvalga', 'uzduotys', 'pranesimai'],
            'atstovavimas' => ['apzvalga', 'institucijos', 'posedziai', 'darbotvarkes_klausimai', 'problemos', 'institucijos_grafas'],
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

describe('route resolution', function () {
    test('every admin route belongs to a workspace or is deliberately workspace-less', function (): void {
        $workspaces = $this->catalog->for(makeAdminUser($this->tenant))['workspaces'];

        $unresolved = adminGetRouteNames()
            ->reject(fn (string $name) => in_array($name, WORKSPACELESS_ROUTES, true))
            ->filter(fn (string $name) => catalogCandidates($workspaces, $name) === [])
            ->values()
            ->all();

        expect($unresolved)->toBeEmpty();
    });

    test('no admin route resolves into two workspaces', function (): void {
        $workspaces = $this->catalog->for(makeAdminUser($this->tenant))['workspaces'];

        $ambiguous = adminGetRouteNames()
            ->filter(fn (string $name) => collect(catalogCandidates($workspaces, $name))->pluck('workspace')->unique()->count() > 1)
            ->values()
            ->all();

        expect($ambiguous)->toBeEmpty();
    });

    test('record pages resolve to the section they belong to', function (string $routeName, string $workspace, string $section): void {
        $workspaces = $this->catalog->for(makeAdminUser($this->tenant))['workspaces'];

        expect(catalogCandidates($workspaces, $routeName)[0] ?? null)->toBe(['workspace' => $workspace, 'section' => $section]);
    })->with([
        'meeting record' => ['meetings.show', 'atstovavimas', 'posedziai'],
        'agenda item editor' => ['agendaItems.edit', 'atstovavimas', 'posedziai'],
        'institution form' => ['institutions.edit', 'atstovavimas', 'institucijos'],
        'reservation record' => ['reservations.show', 'rezervacijos', 'rezervacijos'],
        'reservation resource' => ['reservationResources.show', 'rezervacijos', 'rezervacijos'],
        'news editor' => ['news.edit', 'svetaine', 'naujienos'],
        'duty record' => ['duties.show', 'organizacija', 'pareigybes'],
        'occupancy edit' => ['dutiables.edit', 'organizacija', 'pareigybes'],
        'the duty wizard beats the duties wildcard' => ['duties.updateUsersWizard', 'organizacija', 'pareigybiu_atnaujinimas'],
        'a settings page' => ['settings.site.edit', 'sistema', 'nustatymai'],
        'support request record' => ['supportRequests.show', 'sistema', 'pagalbos_uzklausos'],
    ]);
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
