<?php

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

function dropDeletedAtMigration(): object
{
    return require base_path('database/migrations/2026_09_27_111500_drop_deleted_at_from_reservations_table.php');
}

test('migration permanently cleans up soft-deleted reservations and drops deleted_at columns', function (): void {
    // Simulate legacy state where softDeletes existed
    if (! Schema::hasColumn('reservations', 'deleted_at')) {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    if (! Schema::hasColumn('reservation_resource', 'deleted_at')) {
        Schema::table('reservation_resource', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    $activeReservationId = (string) Str::ulid();
    $deletedReservationId = (string) Str::ulid();
    $resource = Resource::factory()->create();
    $user = User::factory()->create();

    DB::table('reservations')->insert([
        [
            'id' => $activeReservationId,
            'name' => 'Active Reservation',
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'deleted_at' => null,
        ],
        [
            'id' => $deletedReservationId,
            'name' => 'Soft-deleted Reservation',
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'deleted_at' => now(),
        ],
    ]);

    DB::table('reservation_resource')->insert([
        [
            'reservation_id' => $activeReservationId,
            'resource_id' => $resource->id,
            'state' => 'created',
            'quantity' => 1,
            'deleted_at' => null,
        ],
        [
            'reservation_id' => $deletedReservationId,
            'resource_id' => $resource->id,
            'state' => 'created',
            'quantity' => 1,
            'deleted_at' => null,
        ],
    ]);

    DB::table('reservation_user')->insert([
        ['reservation_id' => $activeReservationId, 'user_id' => $user->id],
        ['reservation_id' => $deletedReservationId, 'user_id' => $user->id],
    ]);

    DB::table('comments')->insert([
        [
            'id' => (string) Str::ulid(),
            'commentable_type' => 'reservation',
            'commentable_id' => $activeReservationId,
            'user_id' => $user->id,
            'body' => 'Active comment',
        ],
        [
            'id' => (string) Str::ulid(),
            'commentable_type' => 'reservation',
            'commentable_id' => $deletedReservationId,
            'user_id' => $user->id,
            'body' => 'Deleted reservation comment',
        ],
    ]);

    $migration = dropDeletedAtMigration();
    $migration->up();

    // Verify soft-deleted reservation and its relations were permanently deleted
    expect(DB::table('reservations')->where('id', $deletedReservationId)->exists())->toBeFalse()
        ->and(DB::table('reservation_resource')->where('reservation_id', $deletedReservationId)->exists())->toBeFalse()
        ->and(DB::table('reservation_user')->where('reservation_id', $deletedReservationId)->exists())->toBeFalse()
        ->and(DB::table('comments')->where('commentable_id', $deletedReservationId)->exists())->toBeFalse();

    // Verify active reservation and its relations remain untouched
    expect(DB::table('reservations')->where('id', $activeReservationId)->exists())->toBeTrue()
        ->and(DB::table('reservation_resource')->where('reservation_id', $activeReservationId)->exists())->toBeTrue()
        ->and(DB::table('reservation_user')->where('reservation_id', $activeReservationId)->exists())->toBeTrue()
        ->and(DB::table('comments')->where('commentable_id', $activeReservationId)->exists())->toBeTrue();

    // Verify column was dropped
    expect(Schema::hasColumn('reservations', 'deleted_at'))->toBeFalse()
        ->and(Schema::hasColumn('reservation_resource', 'deleted_at'))->toBeFalse();

    // Test rollback
    $migration->down();

    expect(Schema::hasColumn('reservations', 'deleted_at'))->toBeTrue()
        ->and(Schema::hasColumn('reservation_resource', 'deleted_at'))->toBeTrue();

    // Cleanup: restore dropped state for other tests
    $migration->up();
});
