<?php

use App\Models\NotificationDigestQueue;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccessChangedNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
});

describe('notifications index', function (): void {
    test('user can view notifications page', function (): void {
        asUser($this->user)
            ->get(route('notifications.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotifications')
                ->has('notifications')
            );
    });

    test('notifications page shows user notifications', function (): void {
        // Create some notifications for the user
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));

        asUser($this->user)
            ->get(route('notifications.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotifications')
                ->has('notifications', 2)
            );
    });
});

describe('mark as read', function (): void {
    test('user can mark a notification as read', function (): void {
        $this->user->notify(new WelcomeNotification);
        $notification = $this->user->unreadNotifications()->first();

        expect($notification->read_at)->toBeNull();

        asUser($this->user)
            ->post(route('notifications.markAsRead', $notification->id))
            ->assertRedirect();

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();
    });

    test('user can mark all notifications as read', function (): void {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));

        expect($this->user->unreadNotifications()->count())->toBe(2);

        asUser($this->user)
            ->post(route('notifications.mark-as-read.all'))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->unreadNotifications()->count())->toBe(0);
    });
});

describe('delete single notification', function (): void {
    test('user can delete a single notification', function (): void {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));

        $notification = $this->user->notifications()->first();
        $notificationId = $notification->id;

        expect($this->user->notifications()->count())->toBe(2);

        asUser($this->user)
            ->delete(route('notifications.destroy', $notificationId))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->notifications()->count())->toBe(1)
            ->and($this->user->notifications()->where('id', $notificationId)->exists())->toBeFalse();
    });

    test('deleting one notification does not delete others', function (): void {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));
        $this->user->notify(new WelcomeNotification);

        $notifications = $this->user->notifications()->get();
        $toDelete = $notifications->first();
        $otherIds = $notifications->skip(1)->pluck('id')->toArray();

        asUser($this->user)
            ->delete(route('notifications.destroy', $toDelete->id))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->notifications()->count())->toBe(2);

        foreach ($otherIds as $id) {
            expect($this->user->notifications()->where('id', $id)->exists())->toBeTrue();
        }
    });
});

describe('delete read notifications', function (): void {
    test('user can delete only read notifications', function (): void {
        // Create 3 notifications - 2 unread, 1 read
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));
        $this->user->notify(new WelcomeNotification);

        // Mark only one as read
        $notifications = $this->user->notifications()->get();
        $notifications->first()->update(['read_at' => now()]);

        $this->user->refresh();
        expect($this->user->readNotifications()->count())->toBe(1)
            ->and($this->user->unreadNotifications()->count())->toBe(2);

        // Delete only read notifications
        asUser($this->user)
            ->delete(route('notifications.destroy-all'), ['read_only' => true])
            ->assertRedirect();

        $this->user->refresh();
        // Unread should remain
        expect($this->user->notifications()->count())->toBe(2);
        expect($this->user->readNotifications()->count())->toBe(0)
            ->and($this->user->unreadNotifications()->count())->toBe(2);
    });

    test('deleting read notifications does not affect unread', function (): void {
        // Create notifications
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));

        // Mark one as read
        $this->user->notifications()->first()->update(['read_at' => now()]);

        // Get unread notification ID before deletion
        $unreadNotification = $this->user->unreadNotifications()->first();
        $unreadId = $unreadNotification->id;

        asUser($this->user)
            ->delete(route('notifications.destroy-all'), ['read_only' => true])
            ->assertRedirect();

        $this->user->refresh();

        // Verify the unread notification still exists
        expect($this->user->notifications()->where('id', $unreadId)->exists())->toBeTrue();
        expect($this->user->unreadNotifications()->count())->toBe(1);
    });
});

describe('delete all notifications', function (): void {
    test('user can delete all notifications', function (): void {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));
        $this->user->notify(new WelcomeNotification);

        // Mark one as read
        $this->user->notifications()->first()->update(['read_at' => now()]);

        expect($this->user->notifications()->count())->toBe(3);

        asUser($this->user)
            ->delete(route('notifications.destroy-all'))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->notifications()->count())->toBe(0);
    });

    test('delete all removes both read and unread notifications', function (): void {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new AccessChangedNotification([], now()->toDateString()));

        // Mark one as read
        $this->user->notifications()->first()->update(['read_at' => now()]);

        expect($this->user->readNotifications()->count())->toBe(1)
            ->and($this->user->unreadNotifications()->count())->toBe(1);

        asUser($this->user)
            ->delete(route('notifications.destroy-all'))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->readNotifications()->count())->toBe(0)
            ->and($this->user->unreadNotifications()->count())->toBe(0);
    });
});

describe('authorization', function (): void {
    test('missing notification IDs are forbidden', function (string $method, string $routeName): void {
        asUser($this->user)->{$method}(route($routeName, Str::uuid()->toString()))
            ->assertForbidden();
    })->with([
        'delete' => ['delete', 'notifications.destroy'],
        'mark as read' => ['post', 'notifications.markAsRead'],
    ]);

    test('Inertia requests for missing notification IDs redirect with an error', function (string $method, string $routeName): void {
        $referrer = route('notifications.index');

        asUserWithInertia($this->user)->from($referrer)
            ->{$method}(route($routeName, Str::uuid()->toString()))
            ->assertRedirect($referrer)
            ->assertStatus($method === 'delete' ? 303 : 302)
            ->assertSessionHas('error', __('This action is unauthorized.'));
    })->with([
        'delete' => ['delete', 'notifications.destroy'],
        'mark as read' => ['post', 'notifications.markAsRead'],
    ]);

    test('owned notification actions remove only the matching digest entry', function (string $method, string $routeName): void {
        $this->user->notify(new WelcomeNotification);
        $notification = $this->user->notifications()->first();
        $otherUser = makeUser($this->tenant);
        $otherUser->notify(new WelcomeNotification);
        $otherNotification = $otherUser->notifications()->first();
        $digests = collect([$notification, $otherNotification])->map(fn ($item) => NotificationDigestQueue::create([
            'user_id' => $item->notifiable_id,
            'notification_id' => $item->id,
            'notification_class' => WelcomeNotification::class,
            'category' => 'system',
            'data' => [],
        ]));

        asUser($this->user)->{$method}(route($routeName, $notification->id))->assertRedirect();

        $this->assertModelMissing($digests->first());
        $this->assertModelExists($digests->last());
        $this->assertModelExists($otherNotification);
        expect($otherNotification->refresh()->read_at)->toBeNull();
    })->with([
        'delete' => ['delete', 'notifications.destroy'],
        'mark as read' => ['post', 'notifications.markAsRead'],
    ]);

    test('Inertia denial preserves another users notification and digest entry', function (string $method, string $routeName): void {
        $otherUser = makeUser($this->tenant);
        $otherUser->notify(new WelcomeNotification);
        $notification = $otherUser->notifications()->first();
        $digest = NotificationDigestQueue::create([
            'user_id' => $otherUser->id,
            'notification_id' => $notification->id,
            'notification_class' => WelcomeNotification::class,
            'category' => 'system',
            'data' => [],
        ]);
        $referrer = route('notifications.index');

        asUserWithInertia($this->user)->from($referrer)
            ->{$method}(route($routeName, $notification->id))
            ->assertRedirect($referrer)
            ->assertStatus($method === 'delete' ? 303 : 302)
            ->assertSessionHas('error', __('This action is unauthorized.'));

        $this->assertModelExists($notification);
        $this->assertModelExists($digest);
        expect($notification->refresh()->read_at)->toBeNull();
    })->with([
        'delete' => ['delete', 'notifications.destroy'],
        'mark as read' => ['post', 'notifications.markAsRead'],
    ]);

    test('marking an owned read notification again preserves its read date and clears its digest', function (): void {
        $this->user->notify(new WelcomeNotification);
        $notification = $this->user->notifications()->first();
        $notification->update(['read_at' => now()->subDay()]);
        $readAt = $notification->read_at->toISOString();
        $digest = NotificationDigestQueue::create([
            'user_id' => $this->user->id,
            'notification_id' => $notification->id,
            'notification_class' => WelcomeNotification::class,
            'category' => 'system',
            'data' => [],
        ]);

        asUser($this->user)->post(route('notifications.markAsRead', $notification->id))
            ->assertRedirect();

        expect($notification->refresh()->read_at->toISOString())->toBe($readAt);
        $this->assertModelMissing($digest);
    });

    test('user cannot delete another user notification', function (): void {
        $otherUser = makeUser($this->tenant);
        $otherUser->notify(new WelcomeNotification);

        $notification = $otherUser->notifications()->first();

        asUser($this->user)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertForbidden();

        // Other user's notification should still exist
        expect($otherUser->notifications()->where('id', $notification->id)->exists())->toBeTrue();
    });

    test('user cannot mark another user notification as read', function (): void {
        $otherUser = makeUser($this->tenant);
        $otherUser->notify(new WelcomeNotification);

        $notification = $otherUser->notifications()->first();

        asUser($this->user)
            ->post(route('notifications.markAsRead', $notification->id))
            ->assertForbidden();

        // Other user's notification should still be unread
        $notification->refresh();
        expect($notification->read_at)->toBeNull();
    });
});
