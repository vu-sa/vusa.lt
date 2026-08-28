<?php

use Illuminate\Support\Facades\Artisan;

/**
 * Builds a throwaway docs directory so the command can be exercised without
 * depending on — or writing into — the real docs/ tree, which grows as the team
 * documents and would otherwise make these assertions drift.
 */
function fixtureDocs(array $pages): string
{
    $dir = sys_get_temp_dir().'/docs-coverage-'.uniqid();
    mkdir($dir, 0777, true);

    foreach ($pages as $name => $contents) {
        file_put_contents($dir.'/'.$name, $contents);
    }

    return $dir;
}

function removeFixtureDocs(string $dir): void
{
    foreach (glob($dir.'/*') ?: [] as $file) {
        unlink($file);
    }
    @rmdir($dir);
}

describe('docs:coverage report', function (): void {
    test('reports the feature surface and exits successfully', function (): void {
        $exitCode = Artisan::call('docs:coverage');
        $output = Artisan::output();

        expect($exitCode)->toBe(0)
            ->and($output)->toContain('Feature areas')
            ->and($output)->toContain('documented');
    });

    test('reports both axes: what is documented and what is tested', function (): void {
        Artisan::call('docs:coverage');
        $output = Artisan::output();

        expect($output)->toContain('documented')
            ->and($output)->toContain('tested');
    });

    test('keeps public API routes out of the reported surface', function (): void {
        // /api/v1/* (non-admin) is covered by tests/Feature/Api and never named.
        Artisan::call('docs:coverage', ['--area' => 'reservations']);

        expect(Artisan::output())->not->toContain('api.v1.reservations');
    });

    test('excludes dev tooling areas registered by packages', function (): void {
        Artisan::call('docs:coverage');
        $output = Artisan::output();

        expect($output)->not->toContain('telescope')
            ->and($output)->not->toContain('boost');
    });

    test('narrows the drill-down to a single area', function (): void {
        Artisan::call('docs:coverage', ['--area' => 'reservations']);
        $output = Artisan::output();

        // Every route line in a filtered run belongs to the requested area.
        preg_match_all('/^\s+([a-zA-Z][\w.-]+)\s+(?:tested|untested)$/m', $output, $matches);

        expect($matches[1])->not->toBeEmpty()
            ->and(collect($matches[1])->every(fn ($r) => str_starts_with($r, 'reservations')))->toBeTrue();
    });

    test('credits an area to the page that declares it', function (): void {
        // docs/reservation-system.md declares `area: reservations`.
        Artisan::call('docs:coverage', ['--area' => 'reservations']);

        expect(Artisan::output())->toContain('docs/reservation-system.md');
    });

    test('lists undocumented feature areas as the writing backlog', function (): void {
        Artisan::call('docs:coverage');

        expect(Artisan::output())->toContain('Undocumented feature areas');
    });

    test('a page marked coverage: ignore drops off the no-evidence list', function (): void {
        $docs = fixtureDocs([
            'handbook.md' => "---\ntitle: Handbook\ncoverage: ignore\n---\n\n# Handbook procedure\n",
            'plain.md' => "---\ntitle: Plain\n---\n\n# Plain\n",
        ]);

        try {
            Artisan::call('docs:coverage', ['--docs-path' => $docs]);
            $output = Artisan::output();

            expect($output)->toContain('plain.md')          // still nagged
                ->and($output)->not->toContain('handbook.md'); // opted out entirely
        } finally {
            removeFixtureDocs($docs);
        }
    });
});

describe('docs:coverage strictness', function (): void {
    test('--strict fails when a page cites a test file that does not exist', function (): void {
        $docs = fixtureDocs([
            'gone.md' => "---\ntests:\n  - tests/Feature/GoneForeverTest.php\n---\n\n# Fixture\n",
        ]);

        try {
            expect(Artisan::call('docs:coverage', ['--strict' => true, '--docs-path' => $docs]))->toBe(1)
                ->and(Artisan::output())->toContain('Stale claims');
        } finally {
            removeFixtureDocs($docs);
        }
    });

    test('a stale claim is only fatal under --strict', function (): void {
        $docs = fixtureDocs([
            'gone.md' => "---\ntests:\n  - tests/Feature/GoneForeverTest.php\n---\n\n# Fixture\n",
        ]);

        try {
            expect(Artisan::call('docs:coverage', ['--docs-path' => $docs]))->toBe(0);
        } finally {
            removeFixtureDocs($docs);
        }
    });

    test('is a report, never a gate — an undocumented area does not fail the run', function (): void {
        // The moment the baseline can break CI, someone turns it off and the
        // signal is lost. Only the dangling claim gates, and only under --strict.
        expect(Artisan::call('docs:coverage'))->toBe(0);
    });
});

describe('docs:coverage freshness', function (): void {
    test('flags a page whose cited tests changed after its last review', function (): void {
        $docs = fixtureDocs([
            'stale.md' => "---\narea: pages\nlast_reviewed: 2020-01-01\ntests:\n"
                ."  - tests/Feature/Admin/Content/PageControllerTest.php\n---\n\n# Old\n",
        ]);

        try {
            Artisan::call('docs:coverage', ['--docs-path' => $docs]);
            $output = Artisan::output();

            expect($output)->toContain('may have drifted')
                ->and($output)->toContain('PageControllerTest.php');
        } finally {
            removeFixtureDocs($docs);
        }
    });

    test('does not flag a page reviewed after its tests last changed', function (): void {
        $docs = fixtureDocs([
            'fresh.md' => "---\narea: pages\nlast_reviewed: 2099-01-01\ntests:\n"
                ."  - tests/Feature/Admin/Content/PageControllerTest.php\n---\n\n# Fresh\n",
        ]);

        try {
            Artisan::call('docs:coverage', ['--docs-path' => $docs]);

            expect(Artisan::output())->not->toContain('may have drifted');
        } finally {
            removeFixtureDocs($docs);
        }
    });

    test('nudges a page that cites tests but never records a review', function (): void {
        $docs = fixtureDocs([
            'unreviewed.md' => "---\ntests:\n  - tests/Feature/Admin/Content/PageControllerTest.php\n---\n\n# No date\n",
        ]);

        try {
            Artisan::call('docs:coverage', ['--docs-path' => $docs]);

            expect(Artisan::output())->toContain('Never reviewed');
        } finally {
            removeFixtureDocs($docs);
        }
    });
});

describe('docs:coverage dashboard', function (): void {
    test('writes a dashboard and rewrites it identically on a second run', function (): void {
        $path = 'storage/framework/testing/coverage-dashboard.md';
        $absolute = base_path($path);

        try {
            Artisan::call('docs:coverage', ['--dashboard' => true, '--dashboard-path' => $path]);
            $first = file_get_contents($absolute);

            Artisan::call('docs:coverage', ['--dashboard' => true, '--dashboard-path' => $path]);
            $second = file_get_contents($absolute);

            expect($first)->toContain('Documentation coverage')
                ->and($first)->toContain('Start here')
                ->and($second)->toBe($first);
        } finally {
            @unlink($absolute);
        }
    });
});

describe('docs:coverage review mode', function (): void {
    test('reports when a branch changed no test files', function (): void {
        expect(Artisan::call('docs:coverage', ['--changed' => 'HEAD']))->toBe(0)
            ->and(Artisan::output())->toContain('No');
    });

    test('never fails the run — reviewing is a judgement call', function (): void {
        expect(Artisan::call('docs:coverage', ['--changed' => 'HEAD']))->toBe(0);
    });
});
