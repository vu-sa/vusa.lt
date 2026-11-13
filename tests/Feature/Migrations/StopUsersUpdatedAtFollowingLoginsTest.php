<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('each update time goes back to the last logged edit, or the creation date', function (): void {
    $edited = makeUser(Tenant::query()->firstOrFail());
    $untouched = makeUser(Tenant::query()->firstOrFail());
    DB::table('users')->whereIn('id', [$edited->id, $untouched->id])
        ->update(['created_at' => '2025-01-01 09:00:00', 'updated_at' => '2026-11-13 18:00:00', 'last_action' => '2026-11-13 18:00:00']);
    DB::table('activity_log')->where('subject_type', 'user')->delete();
    DB::table('activity_log')->insert(['log_name' => 'default', 'description' => 'updated', 'event' => 'updated',
        'subject_type' => 'user', 'subject_id' => $edited->id, 'created_at' => '2026-03-02 12:00:00', 'updated_at' => '2026-03-02 12:00:00']);

    $migration = require database_path('migrations/2026_11_13_211019_stop_users_updated_at_following_logins.php');
    $migration->up();

    expect((string) DB::table('users')->where('id', $edited->id)->value('updated_at'))->toBe('2026-03-02 12:00:00')
        ->and((string) DB::table('users')->where('id', $untouched->id)->value('updated_at'))->toBe('2025-01-01 09:00:00');
});
