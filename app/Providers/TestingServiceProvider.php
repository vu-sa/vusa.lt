<?php

namespace App\Providers;

use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Typesense\Client;

/**
 * Hooks that only apply to the testing environment.
 *
 * Currently this isolates Typesense collections per parallel test process.
 * Every parallel process has its own in-memory database (so model ids overlap
 * across processes), but shares one Typesense server. Without isolation, one
 * process can read another process's indexed documents, which makes
 * search-backed assertions flaky under `artisan test --parallel`.
 */
class TestingServiceProvider extends ServiceProvider
{
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

        ParallelTesting::setUpTestCase(function (int $token): void {
            $prefix = "testing_{$token}_";

            config(['scout.prefix' => $prefix]);

            // Clear stale collections from previous runs once per process.
            if (! isset(self::$clearedPrefixes[$prefix])) {
                self::$clearedPrefixes[$prefix] = true;

                $this->deleteCollectionsWithPrefix($prefix);
            }
        });
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
