<?php

use App\Console\Commands\DeploymentRun;

/**
 * The deploy pipeline's ordering *is* its correctness, and almost none of it is exercised by running
 * the app — a mistake here only shows up as a production outage. These assertions pin the orderings
 * that were each established by a real incident.
 */
$shared = '.github/workflows/deploy-common.yml';

$source = fn (string $workflow): string => file_get_contents(base_path($workflow));

$stepsMatching = function (string $workflow, string $needle) use ($source): array {
    // array_slice drops everything before the first `- name:` — the workflow header and its
    // `workflow_call` inputs. Their prose mentions the same commands the steps run, so without this
    // a documentation comment satisfies an assertion about what the deploy actually does.
    $steps = array_slice(preg_split('/^\s*- name:/m', $source($workflow)), 1);

    return array_values(array_filter(
        $steps,
        fn (string $step): bool => str_contains($step, $needle)
    ));
};

/** Position of a step's `- name:` heading within the workflow file. */
$stepPosition = function (string $workflow, string $stepName) use ($source): int {
    $position = strpos($source($workflow), "- name: {$stepName}");

    expect($position)->not->toBeFalse("workflow has no step named '{$stepName}'");

    return $position;
};

describe('the maintenance window', function () use ($shared, $stepsMatching, $stepPosition) {
    // The whole point of the pre-flight phase. Extracting vendor and dumping the database were
    // measured at 10-90s and 8-48s of a 1m33s-4m18s production outage, and neither needs the site
    // down: vendor.new is a scratch dir the running app never reads, and deployment:backup dumps
    // with --single-transaction. Moving either back inside the window silently restores the outage.
    it('finishes the slow, side-effect-free work before taking the site down', function () use ($shared, $stepPosition): void {
        expect($stepPosition($shared, 'Pre-flight (site still online)'))
            ->toBeLessThan($stepPosition($shared, 'Enter maintenance mode'));
    });

    it('does the database backup and the vendor extract in that pre-flight step', function () use ($shared, $stepsMatching): void {
        $preflight = $stepsMatching($shared, 'Pre-flight (site still online)');

        expect($preflight)->toHaveCount(1)
            ->and($preflight[0])->toContain('artisan deployment:backup')
            ->and($preflight[0])->toContain('tar -xzf vendor.tar.gz');
    });

    // public/index.php requires this file before vendor/autoload.php, so it takes the site down with
    // no PHP boot — covering the window where the checkout and vendor/ deliberately disagree.
    it('puts the static maintenance page up before the vendor swap', function () use ($shared, $stepsMatching, $stepPosition): void {
        $upload = $stepsMatching($shared, 'scp deployment/maintenance.php');

        expect($upload)->toHaveCount(1)
            ->and($upload[0])->toContain('storage/framework/maintenance.php')
            ->and($stepPosition($shared, 'Enter maintenance mode'))
            ->toBeLessThan($stepPosition($shared, 'Deploy'));
    });
});

describe('the state the server is in before artisan boots', function () use ($shared, $stepsMatching) {
    // PackageManifest only rebuilds bootstrap/cache/packages.php when the file is missing, so a
    // dropped Composer package stays listed there until something deletes it — and the first artisan
    // boot dies on the missing provider before optimize:clear can run.
    it('clears the cached bootstrap files before artisan boots', function () use ($shared, $stepsMatching): void {
        $steps = $stepsMatching($shared, 'artisan deployment:run');

        expect($steps)->toHaveCount(1)
            ->and($steps[0])->toContain('rm -f bootstrap/cache/*.php')
            ->and(strpos($steps[0], 'rm -f bootstrap/cache/*.php'))
            ->toBeLessThan(strpos($steps[0], 'artisan deployment:run'));
    });

    // Every artisan invocation boots the whole framework first, so new app code paired with the old
    // vendor/ fatals during boot, where no deployment step can recover it.
    it('swaps vendor into place before artisan boots', function () use ($shared, $stepsMatching): void {
        $steps = $stepsMatching($shared, 'artisan deployment:run');

        expect($steps[0])->toContain('mv vendor.new/vendor vendor')
            ->and(strpos($steps[0], 'mv vendor.new/vendor vendor'))
            ->toBeLessThan(strpos($steps[0], 'artisan deployment:run'));
    });

    it('keeps git clean from deleting the archives it just uploaded', function () use ($shared, $stepsMatching): void {
        // The archives sit at the repo root and are not tracked, so a bare `git clean -fd` wipes
        // them — leaving vendor/ unswapped and deployment:deploy-assets with nothing to unpack.
        $steps = $stepsMatching($shared, 'git clean');

        expect($steps)->not->toBeEmpty();

        foreach ($steps as $step) {
            expect($step)->toContain('-e build.tar.gz')
                ->and($step)->toContain('-e docs.tar.gz')
                ->and($step)->toContain('-e vendor.tar.gz')
                ->and($step)->toContain('-e vendor.new');
        }
    });
});

describe('the deploy workflows', function () use ($shared, $source) {
    it('share one implementation so they cannot drift', function () use ($shared, $source): void {
        // These two files were ~90 duplicated lines that had already diverged: staging swallowed
        // deploy failures with `|| true`, production never ran git clean, one cleaned up its SSH keys
        // and the other did not.
        foreach (['.github/workflows/deploy.yml', '.github/workflows/deploy-staging.yml'] as $caller) {
            expect($source($caller))->toContain('uses: ./.github/workflows/deploy-common.yml');
        }

        expect($source($shared))->toContain('workflow_call');
    });

    // Production's repo root holds ~1 GB of untracked directories (two copies of the old LimeSurvey
    // install). `git clean -fd` would delete them silently and irreversibly.
    it('never cleans untracked files on production', function () use ($source): void {
        expect($source('.github/workflows/deploy.yml'))->toContain('clean-untracked: false');
    });

    it('deploys staging automatically from dev', function () use ($source): void {
        $staging = $source('.github/workflows/deploy-staging.yml');

        expect($staging)->toContain('workflow_run')
            ->and($staging)->toContain('- dev');
    });

    it('pins manual staging deployments to a green CI commit containing dev', function () use ($source): void {
        $staging = $source('.github/workflows/deploy-staging.yml');

        expect($staging)->toContain('--commit "$sha"')
            ->and($staging)->toContain('--event push')
            ->and($staging)->toContain('--status success')
            ->and($staging)->toContain('git merge-base --is-ancestor origin/dev "$sha"')
            ->and($staging)->toContain('remote-sha: ${{ needs.resolve.outputs.sha }}')
            ->and($staging)->not->toContain('remote-branch:');
    });

    it('checks out the immutable SHA on the server', function () use ($source): void {
        expect($source('.github/workflows/deploy-common.yml'))
            ->toContain('git checkout --detach --force "${{ inputs.remote-sha }}"')
            ->not->toContain('inputs.remote-branch');
    });

    it('runs CI for pushed feature branches', function () use ($source): void {
        $ci = $source('.github/workflows/ci.yml');

        expect($ci)->toContain("push:\n    branches-ignore:")
            ->not->toContain("push:\n    branches:\n      - main");
    });
});

describe('the deployment pipeline order', function () {
    $keys = array_keys(DeploymentRun::STEPS);
    $indexOf = fn (string $step): int|false => array_search($step, $keys, strict: true);

    // search:reindex drops and recreates all 14 Typesense collections and was measured at 53-63s on
    // every deploy — the largest avoidable slice of downtime, paid even for a CSS-only change. It is
    // non-critical, so running it with the site up degrades search instead of extending the outage.
    it('reindexes search only after the site is back online', function () use ($indexOf): void {
        expect($indexOf('search'))->toBeGreaterThan($indexOf('online'));
    });

    // optimize:clear runs cache:clear, and both restart signals are cache keys — restarting before
    // it would wipe the signal and leave workers running the pre-deploy code.
    it('restarts workers after the caches are rebuilt', function () use ($indexOf): void {
        expect($indexOf('workers'))->toBeGreaterThan($indexOf('optimize'))
            ->and($indexOf('reverb'))->toBeGreaterThan($indexOf('optimize'));
    });

    // Nothing else in the pipeline tells queue:work or reverb:start to pick up new code, and the
    // deploy has already moved vendor/ out from under them. Reverb had 53 days of uptime across
    // dozens of deploys before this step existed.
    it('restarts the queue workers and Reverb at all', function (): void {
        expect(DeploymentRun::STEPS['workers']['command'])->toBe('queue:restart')
            ->and(DeploymentRun::STEPS['reverb']['command'])->toBe('reverb:restart');
    });

    // The backup runs in the workflow's pre-flight phase; this step is the safety net for a hand-run
    // `deployment:run`, and --skip-if-recent is what stops it dumping the database a second time.
    it('does not pay for the backup twice', function (): void {
        expect(DeploymentRun::STEPS['backup']['args'])->toHaveKey('--skip-if-recent');
    });

    // A failing health check used to put the site back into maintenance mode while the step was
    // non-critical — so deployment:run returned 0 and the workflow went green with the site down.
    it('fails the deploy when the health check fails', function (): void {
        expect(DeploymentRun::STEPS['health']['critical'])->toBeTrue()
            ->and(file_get_contents(app_path('Console/Commands/DeploymentHealthCheck.php')))
            ->not->toContain("call('down'");
    });

    // A non-critical step inside the outage is a contradiction: its failure is something we shrug
    // at, yet we are holding the site down for it. Optional work belongs after `online`.
    it('keeps optional work out of the maintenance window', function () use ($keys, $indexOf): void {
        foreach (array_slice($keys, 0, $indexOf('online')) as $step) {
            expect(DeploymentRun::STEPS[$step]['critical'])
                ->toBeTrue("step '{$step}' runs during the outage, so it must be critical — or move it after 'online'");
        }
    });
});

/**
 * Every .ts/.vue/.js under resources/js, concatenated. PHP's glob() has no recursive wildcard —
 * `**` matches a single level — so this walks the tree instead.
 */
function frontendSource(): string
{
    static $cache = null;

    return $cache ??= implode("\n", array_map(
        fn (SplFileInfo $file): string => (string) file_get_contents($file->getPathname()),
        array_filter(
            iterator_to_array(new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(resource_path('js'), FilesystemIterator::SKIP_DOTS)
            ), false),
            fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['ts', 'vue', 'js'], true)
        )
    ));
}

describe('environment template hygiene', function () {
    // Two VITE_ keys (ARCHYVAS/ATSTOVAI passwords) outlived their last consumer and stayed in both
    // .env.example and the deploy workflow, where they were injected into every build as empty
    // strings. Nothing catches that kind of rot by itself, so this does.
    it('declares no VITE_ variable the frontend does not read', function (): void {
        preg_match_all('/^(VITE_[A-Z0-9_]+)=/m', file_get_contents(base_path('.env.example')), $matches);

        $declared = $matches[1];
        expect($declared)->not->toBeEmpty();

        $frontend = frontendSource();
        $unread = array_values(array_filter(
            $declared,
            fn (string $key): bool => ! str_contains($frontend, "import.meta.env.{$key}")
        ));

        // Asserted as a list so a failure names the dead keys instead of just saying "not found".
        expect($unread)->toBe([]);
    });

    it('injects only VITE_ variables the frontend reads', function (): void {
        preg_match_all('/(VITE_[A-Z0-9_]+)=\$\{\{/', file_get_contents(base_path('.github/workflows/deploy-common.yml')), $matches);

        $injected = array_values(array_unique($matches[1]));
        expect($injected)->not->toBeEmpty();

        $frontend = frontendSource();
        $unread = array_values(array_filter(
            $injected,
            fn (string $key): bool => ! str_contains($frontend, "import.meta.env.{$key}")
        ));

        expect($unread)->toBe([]);
    });
});
