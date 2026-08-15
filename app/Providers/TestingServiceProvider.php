<?php

namespace App\Providers;

use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Throwable;
use Typesense\Client;

/**
 * Hooks that only apply to the testing environment.
 *
 * Two separate problems are solved here:
 *
 * 1. ~14 models hard-code the Typesense engine in `searchableUsing()` (see
 *    `App\Models\{User,Duty,Institution,News,Page,...}`), so `SCOUT_DRIVER=database` from
 *    phpunit.xml never applies to them, and `scout.queue` stays on the sync connection.
 *    Every factory `create()` for one of those models was therefore paying a real,
 *    synchronous HTTP round trip to Typesense — measured at ~29ms of the ~35ms it took to
 *    build one `makeUser()` fixture. The suite defaults to Scout's `NullEngine` instead;
 *    tests that actually assert on search results or index state opt back in with the
 *    `usesTypesense()` helper (`tests/Pest.php`), which calls `enableRealTypesense()` below.
 * 2. Once a test opts in, its Typesense collections must be isolated from the collections
 *    the running application uses. Every parallel process has its own in-memory database
 *    (so model ids overlap across processes), but shares one Typesense server. Without a
 *    per-token prefix, one process can read another process's indexed documents, which
 *    makes search-backed assertions flaky under `artisan test --parallel`. A sequential run
 *    fires no `ParallelTesting` token at all, so without the fallback prefix below an
 *    opted-in test would index factory records straight into the shared dev/staging
 *    collections (`documents`, `users`, `institutions`, …) and leave them there.
 *
 * The prefixes never overlap, so clearing one never touches another.
 */
class TestingServiceProvider extends ServiceProvider
{
    /**
     * Prefix applied when the suite runs sequentially.
     *
     * Deliberately not a string prefix of the parallel ones — `deleteCollectionsWithPrefix`
     * matches by `str_starts_with`, so a bare `testing_` would wipe live collections
     * belonging to other parallel processes.
     */
    public const SEQUENTIAL_PREFIX = 'testing_sequential_';

    /**
     * Prefixes already cleared this run. Static so it survives the
     * per-test application rebuilds within a process.
     *
     * @var array<string, true>
     */
    private static array $clearedPrefixes = [];

    public function boot(): void
    {
        if (! $this->app->environment('testing')) {
            return;
        }

        // Default every test to the null engine — see class docblock. NullEngine is
        // Scout's own no-op engine; opting in swaps this back via enableRealTypesense().
        $this->app->make(EngineManager::class)->extend('typesense', fn (): NullEngine => new NullEngine);

        // Tests that call Artisan::down()/up() must not touch the shared
        // storage/framework/down file: under --parallel every process reads it, so one
        // process's maintenance window makes unrelated tests in the others fail with 503s.
        // The array driver keeps the state per process, and MaintenanceModeManager is a
        // singleton, so the middleware and the up/down commands observe the same state.
        config(['app.maintenance.driver' => 'array']);
    }

    /**
     * Restore the real Typesense engine for the current test and isolate its collections.
     *
     * Called by the `usesTypesense()` Pest helper (`tests/Pest.php`), never directly.
     */
    public function enableRealTypesense(): void
    {
        $manager = $this->app->make(EngineManager::class);

        $manager->extend('typesense', fn () => $manager->createTypesenseDriver());
        $manager->forgetEngines();

        $token = ParallelTesting::token();

        $this->useIsolatedPrefix($token !== false ? "testing_{$token}_" : self::SEQUENTIAL_PREFIX);
    }

    /**
     * Point Scout at an isolated prefix, dropping collections left over from earlier runs.
     */
    private function useIsolatedPrefix(string $prefix): void
    {
        config(['scout.prefix' => $prefix]);

        if (isset(self::$clearedPrefixes[$prefix])) {
            return;
        }

        self::$clearedPrefixes[$prefix] = true;

        $this->deleteCollectionsWithPrefix($prefix);
    }

    /**
     * Drop stale collections left behind by earlier runs for the prefix.
     */
    private function deleteCollectionsWithPrefix(string $prefix): void
    {
        try {
            $client = app(Client::class);

            foreach ($client->collections->retrieve() as $collection) {
                if (str_starts_with($collection['name'], $prefix)) {
                    $client->collections[$collection['name']]->delete();
                }
            }
        } catch (Throwable) {
            // Typesense is optional for most of the suite; when it is down,
            // the search-dependent tests surface that themselves.
        }
    }
}
