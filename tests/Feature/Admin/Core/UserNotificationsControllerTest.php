<?php

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TestPushNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
});

describe('notifications index', function () {
    test('user can view notifications page', function () {
        asUser($this->user)
            ->get(route('notifications.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotifications')
                ->has('notifications')
            );
    });

    test('notifications page shows user notifications', function () {
        // Create some notifications for the user
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);

        asUser($this->user)
            ->get(route('notifications.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowNotifications')
                ->has('notifications', 2)
            );
    });
});

describe('mark as read', function () {
    test('user can mark a notification as read', function () {
        $this->user->notify(new WelcomeNotification);
        $notification = $this->user->unreadNotifications()->first();

        expect($notification->read_at)->toBeNull();

        asUser($this->user)
            ->post(route('notifications.markAsRead', $notification->id))
            ->assertRedirect();

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();
    });

    test('user can mark all notifications as read', function () {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);

        expect($this->user->unreadNotifications()->count())->toBe(2);

        asUser($this->user)
            ->post(route('notifications.mark-as-read.all'))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->unreadNotifications()->count())->toBe(0);
    });
});

describe('delete single notification', function () {
    test('user can delete a single notification', function () {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);

        $notification = $this->user->notifications()->first();
        $notificationId = $notification->id;

        expect($this->user->notifications()->count())->toBe(2);

        asUser($this->user)
            ->delete(route('notifications.destroy', $notificationId))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->notifications()->count())->toBe(1);
        expect($this->user->notifications()->where('id', $notificationId)->exists())->toBeFalse();
    });

    test('deleting one notification does not delete others', function () {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);
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

describe('delete read notifications', function () {
    test('user can delete only read notifications', function () {
        // Create 3 notifications - 2 unread, 1 read
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);
        $this->user->notify(new WelcomeNotification);

        // Mark only one as read
        $notifications = $this->user->notifications()->get();
        $notifications->first()->update(['read_at' => now()]);

        $this->user->refresh();
        expect($this->user->readNotifications()->count())->toBe(1);
        expect($this->user->unreadNotifications()->count())->toBe(2);

        // Delete only read notifications
        asUser($this->user)
            ->delete(route('notifications.destroy-all'), ['read_only' => true])
            ->assertRedirect();

        $this->user->refresh();
        // Unread should remain
        expect($this->user->notifications()->count())->toBe(2);
        expect($this->user->readNotifications()->count())->toBe(0);
        expect($this->user->unreadNotifications()->count())->toBe(2);
    });

    test('deleting read notifications does not affect unread', function () {
        // Create notifications
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);

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

describe('delete all notifications', function () {
    test('user can delete all notifications', function () {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);
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

    test('delete all removes both read and unread notifications', function () {
        $this->user->notify(new WelcomeNotification);
        $this->user->notify(new TestPushNotification);

        // Mark one as read
        $this->user->notifications()->first()->update(['read_at' => now()]);

        expect($this->user->readNotifications()->count())->toBe(1);
        expect($this->user->unreadNotifications()->count())->toBe(1);

        asUser($this->user)
            ->delete(route('notifications.destroy-all'))
            ->assertRedirect();

        $this->user->refresh();
        expect($this->user->readNotifications()->count())->toBe(0);
        expect($this->user->unreadNotifications()->count())->toBe(0);
    });
});

describe('authorization', function () {
    test('user cannot delete another user notification', function () {
        $otherUser = makeUser($this->tenant);
        $otherUser->notify(new WelcomeNotification);

        $notification = $otherUser->notifications()->first();

        // Try to delete other user's notification - should redirect but not delete
        asUser($this->user)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect();

        // Other user's notification should still exist
        expect($otherUser->notifications()->where('id', $notification->id)->exists())->toBeTrue();
    });

    test('user cannot mark another user notification as read', function () {
        $otherUser = makeUser($this->tenant);
        $otherUser->notify(new WelcomeNotification);

        $notification = $otherUser->notifications()->first();

        // Try to mark other user's notification as read
        asUser($this->user)
            ->post(route('notifications.markAsRead', $notification->id))
            ->assertRedirect();

        // Other user's notification should still be unread
        $notification->refresh();
        expect($notification->read_at)->toBeNull();
    });
});
