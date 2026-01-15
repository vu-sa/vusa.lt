<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\NotificationDigestQueue;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Trait providing common notification test utilities.
 */
trait NotificationTestHelpers
{
    protected function createUserWithPreferences(array $preferences = []): User
    {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? throw new \RuntimeException('No tenants found in database.');

        $user = User::factory()->hasAttached(
            Duty::factory()->for(Institution::factory()->for($tenant)),
            ['start_date' => now()->subDay()]
        )->create([
            'notification_preferences' => $preferences,
        ]);

        return $user;
    }

    protected function createMutedUser(?Carbon $mutedUntil = null): User
    {
        $mutedUntil = $mutedUntil ?? now()->addHour();

        return $this->createUserWithPreferences([
            'muted_until' => $mutedUntil->toIso8601String(),
        ]);
    }

    protected function createUserWithDisabledChannel(
        NotificationCategory $category,
        NotificationChannel $channel
    ): User {
        $preferences = $this->getDefaultPreferencesWithDisabledChannel($category, $channel);

        return $this->createUserWithPreferences($preferences);
    }

    protected function createUserWithDigestEnabled(int $frequencyHours = 4): User
    {
        $preferences = [
            'digest_frequency_hours' => $frequencyHours,
        ];

        return $this->createUserWithPreferences($preferences);
    }

    protected function getDefaultPreferencesWithDisabledChannel(
        NotificationCategory $category,
        NotificationChannel $channel
    ): array {
        $channels = [];

        foreach (NotificationCategory::cases() as $cat) {
            $channels[$cat->value] = [
                NotificationChannel::InApp->value => true,
                NotificationChannel::Push->value => true,
                NotificationChannel::EmailDigest->value => true,
            ];
        }

        // Disable the specific channel
        $channels[$category->value][$channel->value] = false;

        return ['channels' => $channels];
    }

    protected function createReservationWithResource(User $user): array
    {
        $tenant = $user->current_tenant ?? Tenant::query()->first();

        $reservation = Reservation::factory()->create();
        $reservation->users()->attach($user);

        $resource = Resource::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        // Use DB::table to insert and get the ID, then fetch the model
        // This is needed because Pivot::create() doesn't properly populate the ID
        $id = \Illuminate\Support\Facades\DB::table('reservation_resource')->insertGetId([
            'reservation_id' => $reservation->id,
            'resource_id' => $resource->id,
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(4),
            'quantity' => 1,
            'state' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservationResource = ReservationResource::find($id);

        return [
            'reservation' => $reservation,
            'resource' => $resource,
            'reservationResource' => $reservationResource,
        ];
    }

    protected function createTaskForUser(User $user, array $attributes = []): Task
    {
        $tenant = $user->current_tenant ?? Tenant::query()->first();
        $institution = Institution::factory()->for($tenant)->create();

        $task = Task::factory()->create(array_merge([
            'taskable_type' => Institution::class,
            'taskable_id' => $institution->id,
        ], $attributes));

        $task->users()->attach($user);

        return $task;
    }

    protected function createCommentOnReservationResource(
        ReservationResource $reservationResource,
        User $commenter,
        bool $isDecision = false
    ): Comment {
        return Comment::factory()->create([
            'commentable_type' => ReservationResource::class,
            'commentable_id' => $reservationResource->id,
            'user_id' => $commenter->id,
            'comment' => 'Test comment content',
            'decision' => $isDecision,
        ]);
    }

    protected function transitionReservationResourceTo(
        ReservationResource $reservationResource,
        string $stateClass
    ): void {
        $reservationResource->state->transitionTo($stateClass);
    }

    protected function clearDigestQueue(): void
    {
        NotificationDigestQueue::query()->delete();
    }

    protected function getDigestQueueCountForUser(User $user): int
    {
        return NotificationDigestQueue::where('user_id', $user->id)->count();
    }

    protected function getDigestQueueItemsForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return NotificationDigestQueue::where('user_id', $user->id)->get();
    }
}
