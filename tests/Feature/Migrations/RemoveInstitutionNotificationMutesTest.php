<?php

use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

test('converts only matching muted follows to unfollows and drops the mute table', function (bool $trashed): void {
    $tenant = Tenant::query()->first();
    $institution = Institution::factory()->for($tenant)->create();
    $otherInstitution = Institution::factory()->for($tenant)->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->followedInstitutions()->attach([$institution->id, $otherInstitution->id]);
    $otherUser->followedInstitutions()->attach($institution);

    if ($trashed) {
        $institution->delete();
    }

    $migration = require base_path('database/migrations/2026_10_07_170600_remove_institution_notification_mutes_table.php');
    $migration->down();

    try {
        DB::table('institution_notification_mutes')->insert([
            [
                'id' => strtolower((string) Str::ulid()),
                'user_id' => $user->id,
                'institution_id' => $institution->id,
                'muted_at' => now(),
            ],
            [
                'id' => strtolower((string) Str::ulid()),
                'user_id' => $otherUser->id,
                'institution_id' => $otherInstitution->id,
                'muted_at' => now(),
            ],
        ]);

        $migration->up();

        expect(DB::table('institution_follows')->orderBy('user_id')->orderBy('institution_id')->get(['user_id', 'institution_id'])->map(fn ($row): array => (array) $row)->all())
            ->toEqualCanonicalizing([
                ['user_id' => $user->id, 'institution_id' => $otherInstitution->id],
                ['user_id' => $otherUser->id, 'institution_id' => $institution->id],
            ]);
        expect(Schema::hasTable('institution_notification_mutes'))->toBeFalse();

        $migration->up();

        expect(DB::table('institution_follows')->count())->toBe(2);
    } finally {
        $migration->up();
    }
})->with(['active institution' => false, 'trashed institution' => true]);

test('rollback restores the empty mute schema without reintroducing preferences', function (): void {
    $migration = require base_path('database/migrations/2026_10_07_170600_remove_institution_notification_mutes_table.php');
    $migration->down();

    try {
        expect(Schema::getColumnListing('institution_notification_mutes'))
            ->toBe(['id', 'user_id', 'institution_id', 'muted_at', 'created_at', 'updated_at']);
        expect(DB::table('institution_notification_mutes')->count())->toBe(0);
    } finally {
        $migration->up();
    }
});
