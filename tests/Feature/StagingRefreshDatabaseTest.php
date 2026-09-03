<?php

use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $connection = (string) config('database.default');

    config([
        'app.staging_refresh.expected_database' => config("database.connections.{$connection}.database"),
        'app.staging_refresh.expected_database_username' => 'staging_test_user',
        "database.connections.{$connection}.username" => 'staging_test_user',
    ]);
});

/**
 * This command drops every table in the database it points at. The guard is the only thing that
 * decides which database that is, so it gets tested harder than the happy path.
 */
describe('the environment guard', function () {
    test('it refuses to run outside staging', function (string $environment): void {
        config(['app.env' => $environment]);

        $this->artisan('staging:refresh-database')
            ->expectsOutputToContain('Refused')
            ->assertExitCode(1);
    })->with(['production', 'local', 'testing', '']);

    test('it refuses before reading any configuration', function (): void {
        // A misconfigured source must not be what stops a production run — the guard has to fire
        // first, or the command's safety depends on an unrelated env var being absent.
        config([
            'app.env' => 'production',
            'app.staging_refresh.source_backup_dir' => '/tmp',
        ]);

        $this->artisan('staging:refresh-database')
            ->expectsOutputToContain('Refused')
            ->assertExitCode(1);
    });

    test('it does not drop any tables when refused', function (): void {
        config(['app.env' => 'production']);

        $before = DB::table('users')->count();

        $this->artisan('staging:refresh-database')->assertExitCode(1);

        expect(DB::table('users')->count())->toBe($before);
    });

    test('it refuses a staging database that does not match the expected target', function (): void {
        config([
            'app.env' => 'staging',
            'app.staging_refresh.expected_database' => 'expected_staging_database',
        ]);

        $before = DB::table('users')->count();

        $this->artisan('staging:refresh-database', ['--scrub-only' => true, '--skip-reindex' => true])
            ->expectsOutputToContain('database target is not verified')
            ->assertExitCode(1);

        expect(DB::table('users')->count())->toBe($before);
    });

    test('it refuses a source directory that is its own backups folder', function (): void {
        // Production and staging both have storage/backups on the same VPS. Restoring staging from
        // staging succeeds silently and looks like a working refresh.
        config([
            'app.env' => 'staging',
            'app.staging_refresh.source_backup_dir' => storage_path('backups'),
        ]);
        File::ensureDirectoryExists(storage_path('backups'));

        $this->artisan('staging:refresh-database')
            ->expectsOutputToContain("this application's own storage/backups")
            ->assertExitCode(1);
    });

    test('it gets past the guard in staging and fails on the source instead', function (): void {
        config([
            'app.env' => 'staging',
            'app.staging_refresh.source_backup_dir' => null,
        ]);

        $this->artisan('staging:refresh-database')
            ->expectsOutputToContain('No source directory configured')
            ->assertExitCode(1);
    });
});

describe('scrubbing personal data', function () {
    beforeEach(function () {
        config([
            'app.env' => 'staging',
            'app.staging_refresh.email_allowlist' => 'keep@vusa.lt, second@vusa.lt',
        ]);
    });

    test('it rewrites every address outside the allowlist', function (): void {
        $scrubbed = User::factory()->create(['email' => 'student@stud.vu.lt', 'phone' => '+37060000000']);

        $this->artisan('staging:refresh-database', ['--scrub-only' => true, '--skip-reindex' => true])
            ->assertExitCode(0);

        $scrubbed->refresh();

        expect($scrubbed->email)->toBe("user{$scrubbed->id}@staging.invalid")
            ->and($scrubbed->phone)->toBeNull();
    });

    test('it leaves allowlisted addresses alone so staging mail still reaches someone', function (): void {
        $kept = User::factory()->create(['email' => 'keep@vusa.lt']);

        $this->artisan('staging:refresh-database', ['--scrub-only' => true, '--skip-reindex' => true])
            ->assertExitCode(0);

        expect($kept->refresh()->email)->toBe('keep@vusa.lt');
    });

    test('it leaves no address that could reach a real person', function (): void {
        User::factory()->create(['email' => 'a@stud.vu.lt']);
        User::factory()->create(['email' => 'b@vusa.lt']);
        User::factory()->create(['email' => 'keep@vusa.lt']);

        $this->artisan('staging:refresh-database', ['--scrub-only' => true, '--skip-reindex' => true])
            ->assertExitCode(0);

        $reachable = DB::table('users')
            ->whereNotIn('email', ['keep@vusa.lt', 'second@vusa.lt'])
            ->where('email', 'not like', '%@staging.invalid')
            ->count();

        expect($reachable)->toBe(0);
    });

    test('it empties the tables that would replay production state', function (): void {
        $user = User::factory()->create();

        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\Test',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('push_subscriptions')->insert([
            'subscribable_type' => $user->getMorphClass(),
            'subscribable_id' => $user->id,
            'endpoint' => 'https://push.example.test/subscription',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('staging:refresh-database', ['--scrub-only' => true, '--skip-reindex' => true])
            ->assertExitCode(0);

        expect(DB::table('notifications')->count())->toBe(0)
            ->and(DB::table('push_subscriptions')->count())->toBe(0);
    });

    test('scrub-only still refuses outside staging', function (): void {
        config(['app.env' => 'production']);
        $user = User::factory()->create(['email' => 'student@stud.vu.lt']);

        $this->artisan('staging:refresh-database', ['--scrub-only' => true])->assertExitCode(1);

        expect($user->refresh()->email)->toBe('student@stud.vu.lt');
    });
});

describe('the scheduled task', function () {
    test('it is only scheduled on staging', function (): void {
        // Registered from routes/console.php behind the same environment check, so production's
        // scheduler never even lists it.
        $names = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->description)
            ->all();

        expect($names)->not->toContain('staging:refresh-database');
    });
});
