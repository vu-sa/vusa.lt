<?php

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\UniqueAmongTrashed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

/**
 * Where a database UNIQUE index sits on a soft-deletable table, a trashed row genuinely
 * still occupies the key. `Rule::unique()->withoutTrashed()` would let validation pass
 * and turn the rejection into an "Integrity constraint violation" 500, so the rule keeps
 * rejecting and fixes the message instead.
 */
function validateWith(UniqueAmongTrashed $rule, mixed $value, string $attribute = 'email'): Illuminate\Validation\Validator
{
    return Validator::make([$attribute => $value], [$attribute => $rule]);
}

/** Messages interpolate the displayable attribute name, e.g. "El. paštas" for `email`. */
function expectedMessage(string $key, string $attribute = 'email'): string
{
    return __($key, ['attribute' => __("validation.attributes.{$attribute}")]);
}

test('a value held by a live record is rejected with the standard message', function () {
    $existing = User::factory()->create(['email' => 'taken@vusa.lt']);

    $validator = validateWith(UniqueAmongTrashed::of('users', 'email'), $existing->email);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('email'))->toBe(expectedMessage('validation.unique'));
});

test('a value held by a trashed record explains where the record is', function () {
    $trashed = User::factory()->create(['email' => 'gone@vusa.lt']);
    $trashed->delete();

    $validator = validateWith(UniqueAmongTrashed::of('users', 'email'), 'gone@vusa.lt');

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('email'))
        ->toBe(expectedMessage('validation.unique_trashed'))
        // The whole point: it must not read as a plain "already taken".
        ->not->toBe(expectedMessage('validation.unique'));
});

test('an unused value passes', function () {
    $validator = validateWith(UniqueAmongTrashed::of('users', 'email'), 'fresh@vusa.lt');

    expect($validator->passes())->toBeTrue();
});

test('a record may keep its own value on update', function () {
    $user = User::factory()->create(['email' => 'mine@vusa.lt']);

    $validator = validateWith(
        UniqueAmongTrashed::of('users', 'email')->ignore($user->id),
        'mine@vusa.lt',
    );

    expect($validator->passes())->toBeTrue();
});

test('nested attribute names are checked, not silently skipped', function () {
    // A dotted attribute like `new_users.0.email` gets read as array access by a nested
    // validator, which finds nothing and passes the check vacuously.
    User::factory()->create(['email' => 'dup@vusa.lt']);

    $validator = Validator::make(
        ['new_users' => [['email' => 'dup@vusa.lt']]],
        ['new_users.*.email' => UniqueAmongTrashed::of('users', 'email')],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('new_users.0.email'))->toBeTrue();
});

test('scoping narrows the check to the columns of a composite index', function () {
    $category = Category::factory()->create(['alias' => 'shared-alias']);

    $unscoped = validateWith(UniqueAmongTrashed::of('categories', 'alias'), 'shared-alias', 'alias');
    $scopedElsewhere = validateWith(
        UniqueAmongTrashed::of('categories', 'alias')->where('id', $category->id + 999),
        'shared-alias',
        'alias',
    );

    expect($unscoped->fails())->toBeTrue()
        ->and($scopedElsewhere->passes())->toBeTrue();
});

describe('through the HTTP layer', function () {
    test('creating a user with a trashed user\'s email is refused with the explanatory message', function () {
        $tenant = Tenant::query()->first();
        $admin = makeAdminUser($tenant);

        $trashed = User::factory()->create(['email' => 'returning@vusa.lt']);
        $trashed->delete();

        asUser($admin)
            ->post(route('users.store'), [
                'name' => 'Someone New',
                'email' => 'returning@vusa.lt',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        expect(session('errors')->first('email'))
            ->toBe(expectedMessage('validation.unique_trashed'));
    });
});
