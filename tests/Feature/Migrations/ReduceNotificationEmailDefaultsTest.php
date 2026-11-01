<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('reduces stored reminder defaults without changing unrelated preferences or timestamps', function (?array $settings, ?array $expected, int $frequency, int $expectedFrequency): void {
    $user = User::factory()->create();
    $updatedAt = '2026-01-01 12:00:00';
    $preferences = [
        'reminder_settings' => $settings,
        'digest_frequency_hours' => $frequency,
        'digest_send_hour' => 18,
        'types' => ['task_overdue' => ['email' => 'off']],
        'emails' => ['chosen@example.test'],
        'muted_until' => '2026-12-01T12:00:00+02:00',
    ];
    DB::table('users')->where('id', $user->id)->update([
        'notification_preferences' => json_encode($preferences), 'updated_at' => $updatedAt,
    ]);
    $migration = require database_path('migrations/2026_11_01_000000_reduce_notification_email_defaults.php');

    $migration->up();
    $migration->up();
    $migration->down();

    $row = DB::table('users')->where('id', $user->id)->first();
    expect(json_decode($row->notification_preferences, true))->toBe(array_replace($preferences, [
        'reminder_settings' => $expected, 'digest_frequency_hours' => $expectedFrequency,
    ]))->and($row->updated_at)->toBe($updatedAt);
})->with([
    'old defaults' => [['task_reminder_days' => [7, 3, 1], 'meeting_reminder_hours' => [24, 1]], ['task_reminder_days' => [3], 'meeting_reminder_hours' => [24]], 4, 24],
    'one day and no meetings' => [['task_reminder_days' => [1], 'meeting_reminder_hours' => []], ['task_reminder_days' => [1], 'meeting_reminder_hours' => []], 1, 24],
    'empty task list' => [['task_reminder_days' => [], 'meeting_reminder_hours' => [12, 1]], ['task_reminder_days' => [3], 'meeting_reminder_hours' => [12, 1]], 12, 24],
    'seven days only' => [['task_reminder_days' => [7]], ['task_reminder_days' => [3]], 24, 24],
    'reversed meeting list' => [['meeting_reminder_hours' => [1, 24]], ['meeting_reminder_hours' => [1, 24]], 4, 24],
    'no interval keys' => [[], [], 4, 24],
    'null settings key' => [null, null, 4, 24],
]);

test('leaves null preferences and preferences without reminder settings alone', function (): void {
    $users = [
        User::factory()->create(['notification_preferences' => null]),
        User::factory()->create(['notification_preferences' => ['digest_frequency_hours' => 4]]),
    ];
    $before = DB::table('users')->whereIn('id', collect($users)->pluck('id')->all())->pluck('notification_preferences', 'id')->all();
    $migration = require database_path('migrations/2026_11_01_000000_reduce_notification_email_defaults.php');

    $migration->up();

    expect(DB::table('users')->whereIn('id', collect($users)->pluck('id')->all())->pluck('notification_preferences', 'id')->all())->toBe($before);
});
