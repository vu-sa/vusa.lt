<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Guide pages list their evidence in `tests:` frontmatter, and the web and PDF render it,
 * so a renamed or deleted test must fail here rather than leave a dead citation.
 */
it('cites only test files that exist', function (): void {
    $problems = [];

    foreach (File::allFiles(base_path('docs')) as $file) {
        $page = 'docs/'.$file->getRelativePathname();

        if ($file->getExtension() !== 'md' || preg_match('#^docs/(\.vitepress|public|pdf)/#', $page)) {
            continue;
        }

        if (! preg_match('/\A---\R(.*?)\R---\R/s', $file->getContents(), $frontmatter)) {
            continue;
        }

        foreach ((array) (Yaml::parse($frontmatter[1])['tests'] ?? []) as $path) {
            $isTestPath = (str_starts_with($path, 'tests/') && str_ends_with($path, '.php'))
                || (str_starts_with($path, 'resources/js/') && str_ends_with($path, '.test.ts'));

            if (! $isTestPath || ! File::exists(base_path($path))) {
                $problems[] = "{$page}: {$path}";
            }
        }
    }

    expect($problems)->toBe([], 'Pages cite a missing or non-test file');
});
