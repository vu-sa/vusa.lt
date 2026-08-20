<?php

/**
 * Both deploy workflows must finish putting the server into its new state — new vendor/, no
 * stale bootstrap caches — in plain shell, before the first `php artisan` call. Every artisan
 * invocation boots the whole framework first, so anything still mismatched at that point
 * fatals during boot, where no deployment step can recover it.
 */
$stepsMatching = function (string $workflow, string $needle): array {
    $source = file_get_contents(base_path($workflow));

    return array_values(array_filter(
        preg_split('/^\s*- name:/m', $source),
        fn (string $step): bool => str_contains($step, $needle)
    ));
};

$workflows = [
    '.github/workflows/deploy.yml',
    '.github/workflows/deploy-staging.yml',
];

it('clears the cached bootstrap files before artisan boots', function (string $workflow) use ($stepsMatching): void {
    // PackageManifest only rebuilds bootstrap/cache/packages.php when the file is missing, so
    // a dropped Composer package stays listed there until something deletes it.
    $steps = $stepsMatching($workflow, 'artisan deployment:');

    expect($steps)->not->toBeEmpty();

    foreach ($steps as $step) {
        expect($step)->toContain('rm -f bootstrap/cache/*.php')
            ->and(strpos($step, 'rm -f bootstrap/cache/*.php'))
            ->toBeLessThan(strpos($step, 'artisan deployment:'));
    }
})->with($workflows);

it('swaps vendor into place before artisan boots', function (string $workflow) use ($stepsMatching): void {
    $steps = $stepsMatching($workflow, 'artisan deployment:run');

    expect($steps)->toHaveCount(1);

    foreach ($steps as $step) {
        expect($step)->toContain('mv vendor.new/vendor vendor')
            ->and(strpos($step, 'mv vendor.new/vendor vendor'))
            ->toBeLessThan(strpos($step, 'artisan deployment:run'));
    }
})->with($workflows);

it('puts the static maintenance page up before the vendor swap', function (string $workflow) use ($stepsMatching): void {
    // public/index.php requires this file before vendor/autoload.php, so it takes the site
    // down with no PHP boot — covering the window where checkout and vendor/ disagree.
    $upload = $stepsMatching($workflow, 'scp deployment/maintenance.php');

    expect($upload)->toHaveCount(1)
        ->and($upload[0])->toContain('storage/framework/maintenance.php');
})->with($workflows);

it('keeps git clean from deleting the archives it just uploaded', function () use ($stepsMatching, $workflows): void {
    // The archives sit at the repo root and are not tracked, so a bare `git clean -fd` wipes
    // them — leaving vendor/ unswapped and deployment:deploy-assets with nothing to unpack.
    $steps = array_merge(...array_map(
        fn (string $workflow): array => $stepsMatching($workflow, 'git clean'),
        $workflows
    ));

    expect($steps)->not->toBeEmpty();

    foreach ($steps as $step) {
        expect($step)->toContain('-e build.tar.gz')
            ->and($step)->toContain('-e docs.tar.gz')
            ->and($step)->toContain('-e vendor.tar.gz');
    }
});
