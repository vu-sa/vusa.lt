<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

function userActivity(array $attributes, array $old = [], string $event = 'updated', string $subjectType = 'user'): int
{
    return DB::table('activity_log')->insertGetId([
        'log_name' => 'default', 'description' => $event, 'event' => $event,
        'subject_type' => $subjectType, 'subject_id' => '01jz000000000000000000000a',
        'attribute_changes' => json_encode(['attributes' => $attributes, 'old' => $old]),
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('settings-only user entries go and mixed ones keep their real changes', function (): void {
    $uiOnly = userActivity(['ui_preferences' => ['theme' => 'dark']], ['ui_preferences' => []]);
    $bothPreferences = userActivity(['ui_preferences' => [], 'notification_preferences' => []]);
    $created = userActivity(['name' => 'Petras', 'ui_preferences' => [], 'notification_preferences' => []], event: 'created');
    $edit = userActivity(['phone' => '+370'], ['phone' => null]);
    $otherSubject = userActivity(['ui_preferences' => []], subjectType: 'tenant');

    $migration = require database_path('migrations/2026_11_13_211018_remove_preference_noise_from_user_activity_log.php');
    $migration->up();

    $changes = fn (int $id): array => json_decode(DB::table('activity_log')->where('id', $id)->value('attribute_changes'), true);
    expect(DB::table('activity_log')->whereIn('id', [$uiOnly, $bothPreferences])->count())->toBe(0)
        ->and($changes($created)['attributes'])->toBe(['name' => 'Petras'])
        ->and($changes($edit))->toBe(['attributes' => ['phone' => '+370'], 'old' => ['phone' => null]])
        ->and(DB::table('activity_log')->where('id', $otherSubject)->exists())->toBeTrue();
});
