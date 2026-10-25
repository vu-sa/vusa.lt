<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\UserDuplicateFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
});

function duplicateFilterUser(Tenant $tenant, string $name): User
{
    $user = makeUser($tenant);
    $user->forceFill(['name' => $name])->save();

    return $user;
}

test('initial page and API list the same visible duplicates without exposing other units', function (): void {
    $visible = duplicateFilterUser($this->tenant, 'Jonas Čiurlionis');
    $hidden = duplicateFilterUser(Tenant::query()->whereKeyNot($this->tenant->id)->first(), 'Ciurlionis Jonas');
    duplicateFilterUser($this->tenant, 'Petras Petraitis');

    asUserWithInertia($this->admin)->get(route('users.index', ['duplicates' => 'possible']))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->has('users.data', 1)
        ->where('users.data.0.id', $visible->id)
        ->where('users.data.0.can_update_identity', true));

    $response = asUser($this->admin)->getJson(route('api.v1.admin.users.index', [
        'filters' => json_encode(['duplicates' => 'possible']),
    ]))->assertOk()->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.id', $visible->id)
        ->assertJsonPath('data.items.0.can_update_identity', true);

    expect($response->getContent())->not->toContain($hidden->id)->not->toContain($hidden->email);
});

test('batch detection uses full normalized names and middle-name variants', function (string $first, string $second, bool $matches): void {
    $one = duplicateFilterUser($this->tenant, $first);
    $two = duplicateFilterUser($this->tenant, $second);

    $ids = app(UserDuplicateFinder::class)->ids();

    expect(in_array($one->id, $ids, true))->toBe($matches)
        ->and(in_array($two->id, $ids, true))->toBe($matches);
})->with([
    ['Jonas Čiurlionis', 'Ciurlionis Jonas', true],
    ['Jonas Čiurlionis', 'Jonas Petras Čiurlionis', true],
    ['Jonas Kazlauskas-Petraitis', 'Jonas Kazlauskas', true],
    ['Jonas Jonaitis', 'Jonas Petraitis', false],
    ['Jonas', 'Jonas', false],
    ['Jonas Jonaitis', 'Petras Petraitis', false],
]);

test('protected matches are discoverable even when merging is blocked', function (): void {
    $one = duplicateFilterUser($this->tenant, 'Jonas Jonaitis');
    $two = duplicateFilterUser($this->tenant, 'Jonas Jonaitis');
    $two->assignRole(config('permission.super_admin_role_name'));

    $response = asUser($this->admin)->getJson(route('api.v1.admin.users.index', ['duplicates' => 'possible']))
        ->assertOk()->assertJsonPath('data.total', 2);

    $rows = collect($response->json('data.items'))->keyBy('id');
    expect($rows->keys()->all())->toContain($one->id, $two->id)
        ->and($rows[$two->id]['can_update_identity'])->toBeFalse();
});

test('duplicate filtering happens before pagination and combines with membership and search', function (): void {
    $current = duplicateFilterUser($this->tenant, 'Jonas Jonaitis');
    $former = duplicateFilterUser($this->tenant, 'Jonas Jonaitis');
    $former->dutiables()->update(['start_date' => '2019-01-01', 'end_date' => '2020-01-01']);
    duplicateFilterUser($this->tenant, 'Petras Petraitis');

    asUser($this->admin)->getJson(route('api.v1.admin.users.index', [
        'duplicates' => 'possible', 'per_page' => 1, 'page' => 2,
    ]))->assertOk()->assertJsonPath('data.total', 2)->assertJsonCount(1, 'data.items');

    asUser($this->admin)->getJson(route('api.v1.admin.users.index', [
        'duplicates' => 'possible', 'membership' => 'current', 'search' => 'Jonas',
    ]))->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.id', $current->id);
});

test('a deleted counterpart does not count as a duplicate and trash ignores the filter', function (): void {
    duplicateFilterUser($this->tenant, 'Jonas Jonaitis');
    $deleted = duplicateFilterUser($this->tenant, 'Jonas Jonaitis');
    $deleted->delete();

    asUser($this->admin)->getJson(route('api.v1.admin.users.index', ['duplicates' => 'possible']))
        ->assertOk()->assertJsonPath('data.total', 0);

    asUserWithInertia($this->admin)->get(route('users.index', ['duplicates' => 'possible']))
        ->assertOk()->assertInertia(fn ($page) => $page->where('deletedCount', 1));

    asUser($this->admin)->getJson(route('api.v1.admin.users.index', [
        'duplicates' => 'possible', 'showDeleted' => 'true',
    ]))->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.id', $deleted->id);
});

test('unsupported duplicate filters are rejected', function (bool $jsonFilters): void {
    $parameters = $jsonFilters ? ['filters' => json_encode(['duplicates' => 'all'])] : ['duplicates' => 'all'];

    asUser($this->admin)->getJson(route('api.v1.admin.users.index', $parameters))
        ->assertUnprocessable()->assertJsonValidationErrors('duplicates');
})->with([false, true]);
