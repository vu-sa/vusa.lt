<?php

use App\Models\User;
use App\Notifications\TaskAutoCompletedNotification;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

test('translates only the stored auto-completed task label and keeps the timestamp', function (): void {
    $user = User::factory()->create();
    $updatedAt = '2026-01-01 12:00:00';
    $insert = function (string $type) use ($user, $updatedAt): string {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $type,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'View Details', 'primaryAction' => ['label' => 'View Details', 'url' => '/mano/tasks']]),
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);

        return $id;
    };
    $target = $insert(TaskAutoCompletedNotification::class);
    $other = $insert(TaskOverdueNotification::class);

    $migration = require database_path('migrations/2026_11_01_120000_translate_stored_task_auto_completed_action.php');
    $migration->up();

    $row = DB::table('notifications')->find($target);
    $data = json_decode($row->data, true);

    expect($data['primaryAction']['label'])->toBe('Peržiūrėti')
        ->and($data['title'])->toBe('View Details')
        ->and((string) $row->updated_at)->toBe($updatedAt)
        ->and(json_decode(DB::table('notifications')->find($other)->data, true)['primaryAction']['label'])->toBe('View Details');
});
