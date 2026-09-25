<?php

use App\Actions\GetRecentAccessChanges;
use App\Enums\EmailDelivery;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccessChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-09-20 10:00:00');
    $this->tenant = Tenant::query()->first();
    $this->user = User::factory()->create();
});

function giveTerm(User $user, Tenant $tenant, string $start, ?string $end = null): Duty
{
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $user->duties()->attach($duty, ['start_date' => $start, 'end_date' => $end]);

    return $duty;
}

describe('GetRecentAccessChanges', function (): void {
    test('a term starts on its start date', function (): void {
        giveTerm($this->user, $this->tenant, '2026-09-20');

        $changes = GetRecentAccessChanges::execute($this->user, 0);

        expect($changes)->toHaveCount(1)
            ->and($changes[0]['kind'])->toBe('started')
            ->and($changes[0]['effectiveOn'])->toBe('2026-09-20');
    });

    test('a term ends the day after its last day, not on it', function (): void {
        giveTerm($this->user, $this->tenant, '2026-01-01', '2026-09-20');

        expect(GetRecentAccessChanges::execute($this->user, 0))->toBeEmpty();

        $this->travelTo('2026-09-21 10:00:00');
        $changes = GetRecentAccessChanges::execute($this->user, 0);

        expect($changes)->toHaveCount(1)
            ->and($changes[0]['kind'])->toBe('ended')
            ->and($changes[0]['date'])->toBe('2026-09-20')
            ->and($changes[0]['effectiveOn'])->toBe('2026-09-21');
    });

    test('only the window is reported, newest first, and the limit applies', function (): void {
        giveTerm($this->user, $this->tenant, '2026-09-19');
        giveTerm($this->user, $this->tenant, '2026-09-10');
        giveTerm($this->user, $this->tenant, '2026-06-01');

        expect(array_column(GetRecentAccessChanges::execute($this->user, 14), 'effectiveOn'))->toBe(['2026-09-19', '2026-09-10'])
            ->and(GetRecentAccessChanges::execute($this->user, 365, limit: 1))->toHaveCount(1);
    });

    test('a term that began and ended inside the window is both', function (): void {
        giveTerm($this->user, $this->tenant, '2026-09-10', '2026-09-14');

        expect(array_column(GetRecentAccessChanges::execute($this->user, 14), 'kind'))->toBe(['ended', 'started']);
    });

    test('nobody else\'s terms are included', function (): void {
        giveTerm(User::factory()->create(), $this->tenant, '2026-09-20');

        expect(GetRecentAccessChanges::execute($this->user, 14))->toBeEmpty();
    });
});

describe('notifications:access-changes', function (): void {
    test('tells a person that a term began today', function (): void {
        Notification::fake();
        giveTerm($this->user, $this->tenant, '2026-09-20');

        $this->artisan('notifications:access-changes')->assertSuccessful();

        Notification::assertSentToTimes($this->user, AccessChangedNotification::class, 1);
    });

    test('tells a person that a term ended yesterday', function (): void {
        Notification::fake();
        giveTerm($this->user, $this->tenant, '2026-01-01', '2026-09-19');

        $this->artisan('notifications:access-changes')->assertSuccessful();

        Notification::assertSentToTimes($this->user, AccessChangedNotification::class, 1);
    });

    test('a whole-unit change is one message, not one per duty', function (): void {
        Notification::fake();
        giveTerm($this->user, $this->tenant, '2026-09-20');
        giveTerm($this->user, $this->tenant, '2026-09-20');
        giveTerm($this->user, $this->tenant, '2026-01-01', '2026-09-19');

        $this->artisan('notifications:access-changes')->assertSuccessful();

        Notification::assertSentToTimes($this->user, AccessChangedNotification::class, 1);
        Notification::assertSentTo($this->user, AccessChangedNotification::class, fn (AccessChangedNotification $notification): bool => count($notification->context($this->user)) === 3);
    });

    test('nothing goes to someone whose terms did not change today', function (): void {
        Notification::fake();
        giveTerm($this->user, $this->tenant, '2026-06-01');
        giveTerm($this->user, $this->tenant, '2026-01-01', '2026-08-01');

        $this->artisan('notifications:access-changes')->assertSuccessful();

        Notification::assertNothingSent();
    });

    test('running it twice in a day does not repeat the notice', function (): void {
        giveTerm($this->user, $this->tenant, '2026-09-20');

        $this->artisan('notifications:access-changes')->assertSuccessful();
        $this->artisan('notifications:access-changes')->assertSuccessful();

        expect($this->user->notifications()->where('type', AccessChangedNotification::class)->count())->toBe(1);
    });

    test('it is a digest item, not an email of its own', function (): void {
        $notification = new AccessChangedNotification([], '2026-09-20');

        expect($notification->type()->defaultEmail())->toBe(EmailDelivery::Digest);
    });
});

describe('the history on Mano rolės', function (): void {
    test('lists dated starts and ends, newest first', function (): void {
        giveTerm($this->user, $this->tenant, '2026-09-01');
        giveTerm($this->user, $this->tenant, '2025-10-01', '2026-03-31');

        asUser($this->user)
            ->get(route('profile.roles'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowMyRoles')
                ->where('access.history', fn ($history) => collect($history)->map(fn ($change) => [$change['kind'], $change['date']])->all() === [
                    ['started', '2026-09-01'],
                    ['ended', '2026-03-31'],
                    ['started', '2025-10-01'],
                ])
            );
    });

    test('is empty when nothing changed in the past year', function (): void {
        giveTerm($this->user, $this->tenant, '2020-01-01');

        asUser($this->user)->get(route('profile.roles'))->assertInertia(fn (Assert $page) => $page->has('access.history', 0));
    });
});
