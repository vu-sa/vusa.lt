<?php

use App\Models\Reservation;
use App\Support\Docs\DocClaims;
use App\Support\Docs\FeatureSurfaceScanner;
use App\Support\Docs\TestSurface;

function surface(array $routes): TestSurface
{
    return new TestSurface($routes, [], count($routes), 0);
}

function docClaims(array $claims = [], array $meta = []): DocClaims
{
    return new DocClaims($claims, [], $meta);
}

describe('reportable surface', function (): void {
    test('drops dev tooling and public API, keeps admin API', function (): void {
        expect(FeatureSurfaceScanner::isReportable('telescope.index'))->toBeFalse()
            ->and(FeatureSurfaceScanner::isReportable('api.v1.reservations.index'))->toBeFalse()
            ->and(FeatureSurfaceScanner::isReportable('api.v1.admin.reservations.index'))->toBeTrue()
            ->and(FeatureSurfaceScanner::isReportable('reservations.index'))->toBeTrue();
    });
});

describe('feature area resolution', function (): void {
    test('resolves a route area, morph alias and help fragment to one model', function (): void {
        $features = (new FeatureSurfaceScanner)->scan(surface(['reservations.index' => ['tests/Foo.php']]), docClaims());

        $area = $features->areas['reservations'];

        expect($area->modelAlias)->toBe('reservation')
            ->and($area->modelClass)->toBe(Reservation::class)
            ->and($area->hasHelp)->toBeTrue(); // docs/_parts/reservations exists
    });

    test('marks an area tested when a test names one of its routes', function (): void {
        $features = (new FeatureSurfaceScanner)->scan(surface(['reservations.index' => ['tests/Foo.php']]), docClaims());

        expect($features->areas['reservations']->isTested())->toBeTrue()
            ->and($features->areas['reservations']->testedRoutes)->toContain('reservations.index');
    });
});

describe('documentation attribution', function (): void {
    test('an area is documented when a page declares it by slug', function (): void {
        $meta = ['docs/rez.md' => ['area' => 'reservations', 'models' => [], 'reviewedAt' => null, 'tests' => []]];

        $features = (new FeatureSurfaceScanner)->scan(surface(['reservations.index' => ['tests/Foo.php']]), docClaims([], $meta));

        expect($features->areas['reservations']->isDocumented())->toBeTrue()
            ->and($features->areas['reservations']->docPages)->toContain('docs/rez.md');
    });

    test('an area is documented when a page lists its model', function (): void {
        $meta = ['docs/rez.md' => ['area' => null, 'models' => ['Reservation'], 'reviewedAt' => null, 'tests' => []]];

        $features = (new FeatureSurfaceScanner)->scan(surface(['reservations.index' => ['tests/Foo.php']]), docClaims([], $meta));

        expect($features->areas['reservations']->isDocumented())->toBeTrue();
    });

    test('an incidental test reference does not document an area', function (): void {
        // A reservation page's tests may hit an approval route; that must not
        // count as documenting approvals.
        $surface = surface([
            'reservations.index' => ['tests/ReservationTest.php'],
            'approvals.store' => ['tests/ReservationTest.php'],
        ]);
        $meta = ['docs/rez.md' => ['area' => 'reservations', 'models' => [], 'reviewedAt' => null, 'tests' => ['tests/ReservationTest.php']]];

        $features = (new FeatureSurfaceScanner)->scan($surface, docClaims(['docs/rez.md' => ['tests/ReservationTest.php']], $meta));

        expect($features->areas['reservations']->isDocumented())->toBeTrue()
            ->and($features->areas['approvals']->isDocumented())->toBeFalse();
    });
});

describe('writing backlog', function (): void {
    test('ranks undocumented tested areas and omits documented ones', function (): void {
        $surface = surface([
            'reservations.index' => ['tests/Foo.php'],
            'duties.index' => ['tests/Bar.php'],
        ]);
        $meta = ['docs/rez.md' => ['area' => 'reservations', 'models' => [], 'reviewedAt' => null, 'tests' => []]];

        $backlog = (new FeatureSurfaceScanner)->scan($surface, docClaims([], $meta))->backlog();
        $slugs = array_map(fn ($a) => $a->slug, $backlog);

        expect($slugs)->toContain('duties')
            ->and($slugs)->not->toContain('reservations');
    });
});
