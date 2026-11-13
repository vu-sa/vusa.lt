<?php

use App\Models\Tenant;
use App\Settings\SeasonSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('a settings manager can view and save season settings', function (bool $enabled): void {
    $manager = makeUser(Tenant::query()->first());
    $manager->givePermissionTo('settings.update.*');

    asUser($manager)->get(route('settings.season.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/EditSeasonSettings')
            ->where('canEdit', true)
            ->where('enabled', false)
            ->where('starts_on', '12-01')
            ->where('tree_locks_on', '12-25')
            ->where('ends_on', '01-06')
            ->where('ornament_adjust_minutes', 0)
            ->where('ornament_cooldown_hours', 0));

    asUser($manager)->post(route('settings.season.update'), [
        'enabled' => $enabled,
        'starts_on' => '11-25',
        'tree_locks_on' => '12-20',
        'ends_on' => '01-10',
        'ornament_adjust_minutes' => 10,
        'ornament_cooldown_hours' => 12,
    ])->assertRedirect()->assertSessionHas('success');

    $settings = app(SeasonSettings::class);
    expect($settings->enabled)->toBe($enabled)
        ->and($settings->starts_on)->toBe('11-25')
        ->and($settings->tree_locks_on)->toBe('12-20')
        ->and($settings->ends_on)->toBe('01-10')
        ->and($settings->ornament_adjust_minutes)->toBe(10)
        ->and($settings->ornament_cooldown_hours)->toBe(12);
})->with([true, false]);

test('users without settings access receive 403', function (): void {
    $user = makeUser(Tenant::query()->first());

    asUser($user)->get(route('settings.season.edit'))->assertForbidden();
    asUser($user)->post(route('settings.season.update'), [
        'enabled' => true, 'starts_on' => '11-25', 'tree_locks_on' => '12-20', 'ends_on' => '01-10',
    ])->assertForbidden();

    expect(app(SeasonSettings::class)->enabled)->toBeFalse();
});

test('settings readers can view but cannot change season settings', function (): void {
    $reader = makeUser(Tenant::query()->first());
    $reader->givePermissionTo('settings.read.*');

    asUser($reader)->get(route('settings.season.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canEdit', false));
    asUser($reader)->post(route('settings.season.update'), [
        'enabled' => true, 'starts_on' => '11-25', 'tree_locks_on' => '12-20', 'ends_on' => '01-10',
    ])->assertForbidden();

    expect(app(SeasonSettings::class)->enabled)->toBeFalse();
});

test('invalid recurring dates are rejected without changing settings', function (string $field, mixed $value): void {
    $manager = makeUser(Tenant::query()->first());
    $manager->givePermissionTo('settings.update.*');
    $data = ['enabled' => true, 'starts_on' => '12-01', 'tree_locks_on' => '12-24', 'ends_on' => '01-06', 'ornament_adjust_minutes' => 5, 'ornament_cooldown_hours' => 24];
    $data[$field] = $value;

    asUser($manager)->post(route('settings.season.update'), $data)->assertSessionHasErrors($field);

    $settings = app(SeasonSettings::class);
    expect($settings->enabled)->toBeFalse()
        ->and($settings->starts_on)->toBe('12-01')
        ->and($settings->tree_locks_on)->toBe('12-25')
        ->and($settings->ends_on)->toBe('01-06');
})->with([
    'invalid month' => ['starts_on', '13-01'],
    'impossible day' => ['tree_locks_on', '02-30'],
    'invalid end' => ['ends_on', '04-31'],
    'missing date' => ['starts_on', ''],
    'unpadded date' => ['ends_on', '1-6'],
    'non-string date' => ['tree_locks_on', ['12-24']],
    'non-boolean enabled' => ['enabled', 'yes'],
    'negative adjustment' => ['ornament_adjust_minutes', -1],
    'fractional cooldown' => ['ornament_cooldown_hours', 1.5],
    'missing adjustment' => ['ornament_adjust_minutes', null],
    'lock before the start' => ['tree_locks_on', '11-20'],
    'lock on the start' => ['tree_locks_on', '12-01'],
    'end before the lock' => ['ends_on', '12-10'],
]);

test('dates may run across New Year', function (): void {
    $manager = makeUser(Tenant::query()->first());
    $manager->givePermissionTo('settings.update.*');

    asUser($manager)->post(route('settings.season.update'), [
        'enabled' => true, 'starts_on' => '12-15', 'tree_locks_on' => '01-02', 'ends_on' => '01-10',
        'ornament_adjust_minutes' => 0, 'ornament_cooldown_hours' => 0,
    ])->assertSessionHasNoErrors();

    expect(app(SeasonSettings::class)->tree_locks_on)->toBe('01-02');
});

test('guests must sign in to view or change season settings', function (): void {
    $this->get(route('settings.season.edit'))->assertRedirect();
    $this->post(route('settings.season.update'))->assertRedirect();
});
