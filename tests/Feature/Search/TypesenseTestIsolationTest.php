<?php

use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicInstitution;
use App\Models\PublicMeeting;
use App\Models\PublicNews;
use App\Models\PublicPage;
use App\Models\Resource;
use App\Models\User;
use App\Providers\TestingServiceProvider;
use Illuminate\Support\Str;
use Typesense\Client;

/**
 * Guards the isolation that keeps the suite from indexing factory records into the
 * collections the running application serves. Deliberately sets no `scout.prefix`
 * of its own — the whole point is to assert on whatever the suite booted with.
 */
test('the suite never indexes into the collections the application serves', function (): void {
    expect(config('scout.prefix'))->toStartWith('testing_');

    // Models that force the Typesense engine ignore SCOUT_DRIVER=database, so the
    // prefix is the only thing standing between a factory record and the real index.
    $searchable = [
        Document::class, Duty::class, Institution::class, News::class, Page::class,
        PublicInstitution::class, PublicMeeting::class, PublicNews::class,
        PublicPage::class, Resource::class, User::class,
    ];

    foreach ($searchable as $model) {
        expect((new $model)->searchableAs())->toStartWith('testing_');
    }
});

test('the sequential prefix cannot collide with a parallel token prefix', function (): void {
    // `TestingServiceProvider` clears stale collections by `str_starts_with`, so a
    // sequential prefix that also prefixes `testing_{token}_` would wipe the live
    // collections of sibling processes mid-run.
    expect(TestingServiceProvider::SEQUENTIAL_PREFIX)->toStartWith('testing_');

    foreach (range(0, 32) as $token) {
        expect("testing_{$token}_")->not->toStartWith(TestingServiceProvider::SEQUENTIAL_PREFIX);
    }
});

test('the prune command targets test collections and spares the rest', function (): void {
    $client = app(Client::class);

    // Unique names so this stays safe under --parallel: no sibling process owns them.
    $suffix = Str::random(12);
    $stale = "testing_prune_probe_{$suffix}";
    $keeper = "prune_probe_keeper_{$suffix}";

    foreach ([$stale, $keeper] as $name) {
        $client->collections->create([
            'name' => $name,
            'fields' => [['name' => 'title', 'type' => 'string']],
        ]);
    }

    try {
        // --dry-run only: the real deletion is global, and running it here would
        // drop the live collections of every other parallel process.
        $this->artisan('typesense:prune-test-collections --dry-run')
            ->expectsOutputToContain($stale)
            ->doesntExpectOutputToContain($keeper)
            ->assertSuccessful();
    } finally {
        foreach ([$stale, $keeper] as $name) {
            $client->collections[$name]->delete();
        }
    }
});
