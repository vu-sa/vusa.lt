<?php

// The cloud proxy blocks GitHub's zipball API but allows git. Composer falls back to
// git for packages with a source; for dist-only ones (phpstan/phpstan) build the zip
// from the tag with `git archive` and drop it where Composer's cache will find it.

$lock = json_decode(file_get_contents('composer.lock'), true, flags: JSON_THROW_ON_ERROR);
$cacheDir = trim((string) shell_exec('composer config cache-files-dir 2>/dev/null'));

foreach ([...$lock['packages'], ...$lock['packages-dev']] as $package) {
    $url = $package['dist']['url'] ?? '';

    if (isset($package['source']) || ! preg_match('#^https://api\.github\.com/repos/([^/]+/[^/]+)/zipball/([0-9a-f]{40})$#', $url, $match)) {
        continue;
    }

    $target = "{$cacheDir}/{$package['name']}/".sha1($url).'.zip';

    if (is_file($target)) {
        continue;
    }

    @mkdir(dirname($target), 0777, true);
    $repo = sys_get_temp_dir().'/seed-'.str_replace('/', '-', $match[1]);
    $commands = [
        'rm -rf '.escapeshellarg($repo),
        'git init -q '.escapeshellarg($repo),
        'git -C '.escapeshellarg($repo).' fetch -q --depth 1 '.escapeshellarg("https://github.com/{$match[1]}.git").' '.escapeshellarg($match[2]),
        'git -C '.escapeshellarg($repo).' archive --format=zip --prefix=package/ -o '.escapeshellarg($target).' FETCH_HEAD',
        'rm -rf '.escapeshellarg($repo),
    ];

    passthru(implode(' && ', $commands), $exitCode);
    echo $exitCode === 0 ? "Seeded {$package['name']}\n" : "Could not seed {$package['name']}\n";
}
