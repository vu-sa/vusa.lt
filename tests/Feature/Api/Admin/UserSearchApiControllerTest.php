<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserSimilarityFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
});

describe('search', function (): void {
    test('ranks people in office above former members and dates the former ones', function (): void {
        $former = makeUser($this->tenant);
        $former->update(['name' => 'Aistė Rankauskaitė']);
        $former->dutiables()->update(['start_date' => '2019-07-01', 'end_date' => '2020-06-30']);
        $current = makeUser($this->tenant);
        $current->update(['name' => 'Zita Rankauskienė']);

        $hits = collect(asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', ['search' => 'Rankausk', 'purpose' => 'problem']))
            ->assertOk()
            ->json('data'));

        expect($hits->pluck('id')->all())->toBe([$current->id, $former->id])
            ->and($hits[1]['is_current'])->toBeFalse()
            ->and($hits[1]['last_term_year'])->toBe(2020);
    });

    test('matches on email and returns the full address for a problem', function (): void {
        $target = makeUser($this->tenant);
        $target->update(['name' => 'Petras Petraitis', 'email' => 'unique.handle@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'unique.handle',
                'purpose' => 'problem',
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('id', $target->id);

        expect($match)->toHaveKey('email', 'unique.handle@stud.vu.lt');
    });

    test('includes unclaimed users in problem searches', function (): void {
        $unclaimed = User::factory()->create(['name' => 'Nauja Narė']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Nauja',
                'purpose' => 'problem',
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
                'purpose' => 'problem',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($superAdmin->id);
    });

    test('excludes users outside the caller’s problem scope', function (): void {
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $foreign->update(['name' => 'Svetimas Svetimaitis']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Svetimas',
                'purpose' => 'problem',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->pluck('id'))->not->toContain($foreign->id);
    });

    test('accepts either problem permission on its own', function (string $permission): void {
        $actor = makeUser($this->tenant);
        $actor->duties()->firstOrFail()->givePermissionTo($permission);
        $target = makeUser($this->tenant);
        $target->update(['name' => 'Rūta Leidimaitė']);

        asUser($actor)->getJson(route('api.v1.admin.users.search', [
            'search' => 'Leidimaitė',
            'purpose' => 'problem',
        ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $target->id]);
    })->with([
        'create' => 'problems.create.padalinys',
        'update' => 'problems.update.padalinys',
    ]);

    test('combines the tenants granted by the two problem permissions', function (): void {
        $otherTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $actor = makeUser($this->tenant);
        $actor->duties()->firstOrFail()->givePermissionTo('problems.create.padalinys');
        $otherDuty = Duty::factory()->for(Institution::factory()->for($otherTenant))->create();
        $otherDuty->givePermissionTo('problems.update.padalinys');
        $actor->duties()->attach($otherDuty, ['start_date' => today()->subDay()]);

        $local = makeUser($this->tenant);
        $local->update(['name' => 'Rūta Aprėptis']);
        $foreign = makeUser($otherTenant);
        $foreign->update(['name' => 'Ieva Aprėptis']);

        asUser($actor)->getJson(route('api.v1.admin.users.search', [
            'search' => 'Aprėptis',
            'purpose' => 'problem',
        ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $local->id])
            ->assertJsonFragment(['id' => $foreign->id]);
    });

    test('searches every tenant when either problem permission is global', function (): void {
        $actor = makeUser($this->tenant);
        $actor->duties()->firstOrFail()->givePermissionTo(['problems.create.padalinys', 'problems.update.*']);
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $foreign->update(['name' => 'Agnė Visuotinė', 'email' => 'visuotine@stud.vu.lt']);

        $response = asUser($actor)->getJson(route('api.v1.admin.users.search', [
            'search' => 'Visuotinė',
            'purpose' => 'problem',
        ]))->assertOk();

        $match = collect($response->json('data'))->firstWhere('id', $foreign->id);

        expect($match)->toHaveKey('email', 'visuotine@stud.vu.lt');
    });

    test('duty-member finds people across tenants and masks foreign emails', function (): void {
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys', 'shortname' => 'VU SA TEST']));
        $foreign->update(['name' => 'Svetimas Svetimaitis', 'email' => 'svetimas@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Svetimas',
                'purpose' => 'duty-member',
            ]))
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('id', $foreign->id);

        expect($match)->not->toBeNull()
            ->and($match['tenants'])->toContain('VU SA TEST')
            ->and($match['email'])->toBe('s***@stud.vu.lt');
    });

    test('duty-member shows full emails in the caller’s duty scope', function (): void {
        $own = makeUser($this->tenant);
        $own->update(['name' => 'Savas Savaitis', 'email' => 'savas@stud.vu.lt']);

        $response = asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', [
                'search' => 'Savas',
                'purpose' => 'duty-member',
            ]))
            ->assertStatus(200);

        expect(collect($response->json('data'))->firstWhere('id', $own->id)['email'])
            ->toBe('savas@stud.vu.lt');
    });

    test('returns 403 for a restricted purpose when the caller has no grant', function (string $purpose): void {
        asUser(makeUser($this->tenant))
            ->getJson(route('api.v1.admin.users.search', ['search' => 'Jonas', 'purpose' => $purpose]))
            ->assertForbidden();
    })->with(['duty-member', 'problem']);

    test('involve finds someone in another tenant without returning an email', function (): void {
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $foreign->update(['name' => 'Svetimas Svetimaitis', 'email' => 'svetimas@stud.vu.lt']);

        $response = asUser(makeUser($this->tenant))
            ->getJson(route('api.v1.admin.users.search', ['search' => 'Svetimas', 'purpose' => 'involve']))
            ->assertOk();

        $match = collect($response->json('data'))->firstWhere('id', $foreign->id);

        expect($match)->toHaveKey('name', 'Svetimas Svetimaitis')
            ->not->toHaveKey('email');
    });

    test('involve does not match on email, so it cannot reveal whose address it is', function (): void {
        $foreign = makeUser(Tenant::factory()->create(['type' => 'padalinys']));
        $foreign->update(['name' => 'Svetimas Svetimaitis', 'email' => 'unique.handle@stud.vu.lt']);

        $response = asUser(makeUser($this->tenant))
            ->getJson(route('api.v1.admin.users.search', ['search' => 'unique.handle', 'purpose' => 'involve']))
            ->assertOk();

        expect($response->json('data'))->toBe([]);
    });

    test('returns 401 for involve when the caller is a guest', function (): void {
        $this->getJson(route('api.v1.admin.users.search', ['search' => 'Jonas', 'purpose' => 'involve']))
            ->assertUnauthorized();
    });

    test('returns 422 for an unknown or missing purpose', function (array $query): void {
        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', ['search' => 'Jonas', ...$query]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('purpose');
    })->with([
        'unknown purpose' => [['purpose' => 'everything']],
        'missing purpose' => [[]],
    ]);

    test('still requires at least two characters', function (): void {
        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.users.search', ['search' => 'a', 'purpose' => 'problem']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
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

    test('names match the way the merge guard needs them to', function (string $first, string $second, bool $matches): void {
        expect(UserSimilarityFinder::namesMatch($first, $second))->toBe($matches);
    })->with([
        'reordered, no diacritics' => ['Jonas Čiurlionis', 'Ciurlionis Jonas', true],
        'dashed surname' => ['Ona Pavardė-Pavardienė', 'Ona Pavardė Pavardienė', true],
        'middle name' => ['Justinas Petras Lisauskas', 'Justinas Lisauskas', true],
        'one shared part' => ['Justinas Lisauskas', 'Justinas Petraitis', false],
    ]);

    test('masking leaves the domain readable', function (): void {
        expect(UserSimilarityFinder::maskEmail('jonas.jonaitis@stud.vu.lt'))->toBe('j***@stud.vu.lt')
            ->and(UserSimilarityFinder::maskEmail(null))->toBeEmpty();
    });
});
