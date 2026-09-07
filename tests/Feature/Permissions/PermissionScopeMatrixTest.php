<?php

/**
 * The {resource}.{action}.{scope} grid
 *
 * Per-controller tests assert their own resource's happy path; nothing asserted the scope
 * semantics themselves uniformly. This walks the grid for a representative set of
 * resources, each chosen because it exercises a different branch of
 * `HasCommonChecks::commonChecker()`:
 *
 * - news         — a plain single-tenant model, the common case
 * - resources    — a single-tenant model whose role also holds a `*`-scoped read
 * - institutions — the only resource with a meaningful `own` scope
 * - duties       — `own` resolves against the duty itself, not a relation
 * - tags         — globally scoped: no tenant relation at all, so the padalinys branch
 *                  must deny explicitly rather than throw
 */

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Permission;
use App\Models\Problem;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\StudySet;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantA = Tenant::query()->first();
    $this->tenantB = Tenant::query()->where('id', '!=', $this->tenantA->id)->first();
});

/**
 * A user in $tenant whose duty holds exactly $permissions — no more.
 */
function actorGranted(Tenant $tenant, array $permissions): User
{
    $role = Role::firstOrCreate(['name' => 'Matrix '.md5(implode('|', $permissions)), 'guard_name' => 'web']);
    $role->syncPermissions(collect($permissions)->map(
        fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
    )->all());

    $user = makeUser($tenant);
    $user->duties()->first()->assignRole($role);

    return $user;
}

function subjectIn(string $resource, Tenant $tenant): mixed
{
    return match ($resource) {
        'news' => News::factory()->create(['tenant_id' => $tenant->id]),
        'resources' => Resource::factory()->create([
            'tenant_id' => $tenant->id,
            'resource_category_id' => ResourceCategory::factory()->create()->id,
        ]),
        'institutions' => Institution::factory()->for($tenant)->create(),
        'documents' => Document::factory()->for(Institution::factory()->for($tenant))->create(),
        'meetings' => Meeting::factory()->hasAttached(Institution::factory()->for($tenant))->create(),
        'calendars' => Calendar::factory()->create(['tenant_id' => $tenant->id]),
        'forms' => Form::factory()->create(['tenant_id' => $tenant->id]),
        'banners' => Banner::factory()->create(['tenant_id' => $tenant->id]),
        'problems' => Problem::factory()->create(['tenant_id' => $tenant->id]),
        'studyPrograms' => StudyProgram::factory()->create(['tenant_id' => $tenant->id]),
        'quickLinks' => QuickLink::factory()->create(['tenant_id' => $tenant->id]),
        'tasks' => Task::factory()->hasAttached(makeUser($tenant))->create(),
        'studySets' => StudySet::factory()->create(['tenant_id' => $tenant->id]),
    };
}

describe('padalinys scope', function (): void {
    test('grants the actors own tenant and refuses every other one', function (string $resource, string $ability): void {
        $permission = "{$resource}.{$ability}.padalinys";
        $actor = actorGranted($this->tenantA, [$permission]);

        expect(Gate::forUser($actor)->allows($ability, subjectIn($resource, $this->tenantA)))->toBeTrue()
            ->and(Gate::forUser($actor)->allows($ability, subjectIn($resource, $this->tenantB)))->toBeFalse();
    })->with([
        ['news', 'update'],
        ['news', 'delete'],
        ['resources', 'update'],
        ['resources', 'delete'],
        ['institutions', 'update'],
        ['documents', 'update'],
        ['documents', 'delete'],
        ['meetings', 'update'],
        ['meetings', 'delete'],
        ['calendars', 'update'],
        ['calendars', 'delete'],
        ['forms', 'update'],
        ['forms', 'delete'],
        ['banners', 'update'],
        ['banners', 'delete'],
        ['problems', 'update'],
        ['problems', 'delete'],
        ['studyPrograms', 'update'],
        ['studyPrograms', 'delete'],
        ['quickLinks', 'update'],
        ['quickLinks', 'delete'],
        ['tasks', 'update'],
        ['tasks', 'delete'],
        ['studySets', 'update'],
        ['studySets', 'delete'],
    ]);

    test('a different action at the same scope is not granted', function (): void {
        $actor = actorGranted($this->tenantA, ['news.update.padalinys']);

        expect(Gate::forUser($actor)->allows('update', subjectIn('news', $this->tenantA)))->toBeTrue()
            ->and(Gate::forUser($actor)->allows('delete', subjectIn('news', $this->tenantA)))->toBeFalse();
    });
});

describe('all (*) scope', function (): void {
    test('reaches every tenant', function (string $resource, string $ability): void {
        $actor = actorGranted($this->tenantA, ["{$resource}.{$ability}.*"]);

        expect(Gate::forUser($actor)->allows($ability, subjectIn($resource, $this->tenantA)))->toBeTrue()
            ->and(Gate::forUser($actor)->allows($ability, subjectIn($resource, $this->tenantB)))->toBeTrue();
    })->with([
        ['news', 'update'],
        ['news', 'delete'],
        ['resources', 'update'],
        ['institutions', 'update'],
        ['documents', 'update'],
        ['documents', 'delete'],
        ['meetings', 'update'],
        ['meetings', 'delete'],
        ['calendars', 'update'],
        ['calendars', 'delete'],
        ['forms', 'update'],
        ['forms', 'delete'],
        ['banners', 'update'],
        ['banners', 'delete'],
        ['problems', 'update'],
        ['problems', 'delete'],
        ['studyPrograms', 'update'],
        ['studyPrograms', 'delete'],
        ['quickLinks', 'update'],
        ['quickLinks', 'delete'],
        ['tasks', 'update'],
        ['tasks', 'delete'],
        ['studySets', 'update'],
        ['studySets', 'delete'],
    ]);

    test('one resources global scope does not reach another resource', function (): void {
        $actor = actorGranted($this->tenantA, ['resources.read.*', 'news.update.padalinys']);

        expect(Gate::forUser($actor)->allows('update', subjectIn('news', $this->tenantB)))->toBeFalse();
    });
});

describe('own scope', function (): void {
    test('institutions: reaches the actors own institution only', function (): void {
        $actor = actorGranted($this->tenantA, ['institutions.read.own']);

        $ownInstitution = $actor->duties()->first()->institution;
        $siblingInstitution = Institution::factory()->for($this->tenantA)->create();

        expect(Gate::forUser($actor)->allows('view', $ownInstitution))->toBeTrue()
            ->and(Gate::forUser($actor)->allows('view', $siblingInstitution))->toBeFalse();
    });

    test('duties: resolves against the duty itself, not a relation', function (): void {
        $actor = actorGranted($this->tenantA, ['duties.update.own']);

        $ownDuty = $actor->duties()->first();
        $siblingDuty = Duty::factory()->for(Institution::factory()->for($this->tenantA))->create();

        expect(Gate::forUser($actor)->allows('update', $ownDuty))->toBeTrue()
            ->and(Gate::forUser($actor)->allows('update', $siblingDuty))->toBeFalse();
    });

    test('own does not imply the tenant', function (): void {
        $actor = actorGranted($this->tenantA, ['institutions.read.own']);

        expect(Gate::forUser($actor)->allows('view', Institution::factory()->for($this->tenantB)->create()))
            ->toBeFalse();
    });
});

describe('globally scoped resources', function (): void {
    test('tags are reachable only through the global scope', function (): void {
        $globalActor = actorGranted($this->tenantA, ['tags.update.*']);
        $tenantActor = actorGranted($this->tenantA, ['tags.update.padalinys']);
        $tag = Tag::factory()->create();

        expect(Gate::forUser($globalActor)->allows('update', $tag))->toBeTrue()
            // Tag has no tenant relation; the padalinys branch must deny, not throw.
            ->and(Gate::forUser($tenantActor)->allows('update', $tag))->toBeFalse();
    });
});

describe('no permission at all', function (): void {
    test('a duty in the tenant grants nothing on its own', function (string $resource, string $ability): void {
        $actor = makeUser($this->tenantA);

        expect(Gate::forUser($actor)->allows($ability, subjectIn($resource, $this->tenantA)))->toBeFalse();
    })->with([
        ['news', 'update'],
        ['news', 'delete'],
        ['resources', 'update'],
        ['institutions', 'update'],
        ['documents', 'update'],
        ['documents', 'delete'],
        ['meetings', 'update'],
        ['meetings', 'delete'],
        ['calendars', 'update'],
        ['calendars', 'delete'],
        ['forms', 'update'],
        ['forms', 'delete'],
        ['banners', 'update'],
        ['banners', 'delete'],
        ['problems', 'update'],
        ['problems', 'delete'],
        ['studyPrograms', 'update'],
        ['studyPrograms', 'delete'],
        ['quickLinks', 'update'],
        ['quickLinks', 'delete'],
        ['tasks', 'update'],
        ['tasks', 'delete'],
        ['studySets', 'update'],
        ['studySets', 'delete'],
    ]);
});
