<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\UserSimilarityFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
});

describe('search', function (): void {
    test('matches on email as well as name', function (): void {
        $target = makeUser($this->tenant);
        $target->update(['name' => 'Petras Petraitis', 'email' => 'unique.handle@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'unique.handle',
                'permission' => 'users.read.padalinys',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->toContain($target->id);
    });

    test('returns users with no duties so they can be given one', function (): void {
        $unclaimed = User::factory()->create(['name' => 'Nauja Narė']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Nauja',
                'permission' => 'users.read.padalinys',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->toContain($unclaimed->id);
    });

    test('excludes a duty-less user holding a directly assigned role', function (): void {
        $superAdmin = User::factory()->create(['name' => 'Nauja Administratorė']);
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Nauja',
                'permission' => 'users.read.padalinys',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($superAdmin->id);
    });

    test('excludes a claimed user from another tenant by default', function (): void {
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $foreign->update(['name' => 'Svetimas Svetimaitis']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Svetimas',
                'permission' => 'users.read.padalinys',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($foreign->id);
    });

    test('scope=all returns people from every tenant', function (): void {
        // The duty wizard needs this: assigning somebody from another unit is how a
        // person joins a new one. Hiding them is what makes admins create a second
        // account for a person who already exists.
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys', 'shortname' => 'VU SA TEST']));
        $foreign->update(['name' => 'Svetimas Svetimaitis', 'email' => 'svetimas@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Svetimas',
                'permission' => 'duties.update.padalinys',
                'scope' => 'all',
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('id', $foreign->id);

        expect($match)->not->toBeNull()
            // The unit is what lets an admin tell two identical names apart.
            ->and($match['tenants'])->toContain('VU SA TEST')
            // ...but a global picker must not double as an address book.
            ->and($match['email'])->toBe('s***@stud.vu.lt');
    });

    test('scope=all still shows full emails for the admin own tenant', function (): void {
        $own = makeUser($this->tenant);
        $own->update(['name' => 'Savas Savaitis', 'email' => 'savas@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Savas',
                'permission' => 'users.read.padalinys',
                'scope' => 'all',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->firstWhere('id', $own->id)['email'])
            ->toBe('savas@stud.vu.lt');
    });

    test('rejects an unknown scope rather than silently widening', function (): void {
        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', ['search' => 'Jonas', 'scope' => 'everything']))
            ->assertStatus(422);
    });

    test('still requires at least two characters', function (): void {
        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', ['search' => 'a']))
            ->assertStatus(422);
    });
});

describe('similar', function (): void {
    test('finds a same-named person in a different tenant', function (): void {
        // The whole point: the duplicate an admin is about to create almost always
        // already exists somewhere they cannot see.
        $existing = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $existing->update(['name' => 'Jonas Jonaitis', 'email' => 'jonas.jonaitis@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', [
                'name' => 'Jonas Jonaitis',
                'email' => 'j.jonaitis@vusa.lt',
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('id', $existing->id);

        expect($match)->not->toBeNull()
            ->and($match['reason'])->toBe('name')
            ->and($match['can_manage'])->toBeFalse();
    });

    test('masks the email so the endpoint cannot be used to harvest addresses', function (): void {
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Ona Onaitė', 'email' => 'ona.onaite@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Ona Onaitė']))
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('id', $existing->id);

        expect($match['email_masked'])->toBe('o***@stud.vu.lt')
            ->and($response->json('data'))->each(fn ($row) => $row->not->toHaveKey('email'));
    });

    test('recognises the same local part under a different domain', function (): void {
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Kitas Vardas', 'email' => 'vardas.pavarde@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', [
                'name' => 'Visai Kitoks Vardas',
                'email' => 'vardas.pavarde@vusa.lt',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->firstWhere('id', $existing->id)['reason'])
            ->toBe('email_local_part');
    });

    test('reports can_manage for a person the admin actually administers', function (): void {
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Savas Savaitis']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Savas Savaitis']))
            ->assertStatus(200);

        expect(collect($response->json('data'))->firstWhere('id', $existing->id)['can_manage'])->toBeTrue();
    });

    test('a shared first name alone is not a match', function (): void {
        // The complaint that prompted the stricter rule: typing "Justinas Lisauskas"
        // surfaced every Justinas in the organisation. One name part in common is a
        // common first name, not a duplicate.
        $unrelated = makeUser($this->tenant);
        $unrelated->update(['name' => 'Justinas Petraitis', 'email' => 'justinas.p@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Justinas Lisauskas']))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($unrelated->id);
    });

    test('a shared surname alone is not a match', function (): void {
        $sibling = makeUser($this->tenant);
        $sibling->update(['name' => 'Ona Lisauskaite', 'email' => 'ona.l@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Justinas Lisauskas']))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($sibling->id);
    });

    test('a full name with nobody like it returns nothing at all', function (): void {
        makeUser($this->tenant)->update(['name' => 'Justinas Petraitis']);
        makeUser($this->tenant)->update(['name' => 'Ona Lisauskaite']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Justinas Lisauskas']))
            ->assertStatus(200);

        expect($response->json('data'))->toBe([]);
    });

    test('a middle name does not hide an existing person', function (): void {
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Justinas Lisauskas', 'email' => 'j.lisauskas@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Justinas Petras Lisauskas']))
            ->assertStatus(200);

        expect(collect($response->json('data'))->firstWhere('id', $existing->id)['reason'])
            ->toBe('name_variant');
    });

    test('two first names are matched however many of them are typed', function (): void {
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Vardas Antrasis Pavardenis', 'email' => 'v.antrasis@stud.vu.lt']);

        foreach (['Vardas Pavardenis', 'Antrasis Pavardenis', 'Pavardenis Vardas'] as $typed) {
            $response = asUser($this->coordinator)
                ->getJson(route('api.v1.admin.users.similar', ['name' => $typed]))
                ->assertStatus(200);

            // toContain() takes varargs, so the spelling under test goes in a
            // separate assertion rather than as a message argument.
            expect(collect($response->json('data'))->pluck('id')->all())
                ->toContain($existing->id);
        }
    });

    test('sharing two first names but not the surname is a different person', function (): void {
        // The guard that keeps people with two first names from colliding with
        // each other: two parts in common is not enough unless one name is
        // wholly contained in the other.
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Vardas Antrasis Pavardenis', 'email' => 'v.antrasis@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Vardas Antrasis Kitoks']))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($existing->id);
    });

    test('a double-barrelled surname matches however it is punctuated', function (): void {
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Vardas Pirmoji-Antroji', 'email' => 'v.pirmoji@stud.vu.lt']);

        foreach (['Vardas Pirmoji-Antroji', 'Vardas Pirmoji Antroji', 'Vardas Pirmoji'] as $typed) {
            $response = asUser($this->coordinator)
                ->getJson(route('api.v1.admin.users.similar', ['name' => $typed]))
                ->assertStatus(200);

            // toContain() takes varargs, so the spelling under test goes in a
            // separate assertion rather than as a message argument.
            expect(collect($response->json('data'))->pluck('id')->all())
                ->toContain($existing->id);
        }
    });

    test('č, š and ž are found even when typed without the diacritic', function (): void {
        // users.name collates as utf8mb4_lithuanian_ci, which folds ą ę ė į ų ū but
        // treats č, š and ž as separate letters — so the candidate query cannot rely
        // on the collation for these three.
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Jonas Čižauskšas', 'email' => 'j.cizausksas@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Jonas Cizausksas']))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->toContain($existing->id);
    });

    test('a single name part is never enough to query on', function (): void {
        makeUser($this->tenant)->update(['name' => 'Justinas Lisauskas']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Justinas']))
            ->assertStatus(200);

        expect($response->json('data'))->toBe([]);
    });

    test('an exact email still matches even with an unrelated name', function (): void {
        // The strict name rule must not suppress the strongest signal there is.
        $existing = makeUser($this->tenant);
        $existing->update(['name' => 'Visai Kitas', 'email' => 'shared.address@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.similar', [
                'name' => 'Justinas Lisauskas',
                'email' => 'shared.address@stud.vu.lt',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->firstWhere('id', $existing->id)['reason'])->toBe('email');
    });

    test('a user without users.create permission is refused', function (): void {
        asUser(makeUser($this->tenant))
            ->getJson(route('api.v1.admin.users.similar', ['name' => 'Jonas Jonaitis']))
            ->assertStatus(403);
    });
});

describe('name normalisation', function (): void {
    test('ignores diacritics, case and word order', function (): void {
        expect(UserSimilarityFinder::normaliseName('Jonas Čiurlionis'))
            ->toBe(UserSimilarityFinder::normaliseName('ciurlionis  JONAS'));
    });

    test('does not collapse genuinely different names', function (): void {
        expect(UserSimilarityFinder::normaliseName('Jonas Jonaitis'))
            ->not->toBe(UserSimilarityFinder::normaliseName('Jonas Petraitis'));
    });

    test('masking leaves the domain readable', function (): void {
        expect(UserSimilarityFinder::maskEmail('jonas.jonaitis@stud.vu.lt'))->toBe('j***@stud.vu.lt')
            ->and(UserSimilarityFinder::maskEmail(null))->toBeEmpty();
    });
});
