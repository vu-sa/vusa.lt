<?php

namespace App\Providers;

use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Typesense\Client;

/**
 * Hooks that only apply to the testing environment.
 *
 * Currently this isolates Typesense collections from the collections the running
 * application uses. Two separate problems are solved here:
 *
 * 1. Every parallel process has its own in-memory database (so model ids overlap
 *    across processes), but shares one Typesense server. Without a per-token
 *    prefix, one process can read another process's indexed documents, which
 *    makes search-backed assertions flaky under `artisan test --parallel`.
 * 2. A sequential run fires no `ParallelTesting` hooks at all, so without the
 *    fallback prefix below the suite indexes factory records straight into the
 *    shared dev/staging collections (`documents`, `users`, `institutions`, …)
 *    and leaves them there — the models that force the Typesense engine ignore
 *    `SCOUT_DRIVER=database` from phpunit.xml.
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

        // Applies to sequential runs. Under `--parallel` this is immediately
        // superseded per test case by the token-scoped prefix below.
        $this->useIsolatedPrefix(self::SEQUENTIAL_PREFIX);

        ParallelTesting::setUpTestCase(function (int $token): void {
            $this->useIsolatedPrefix("testing_{$token}_");
        });
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
