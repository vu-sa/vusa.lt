<?php

/**
 * Cloudflare Rocket Loader defers every script without data-cfasync="false"; the theme script
 * then runs after the SSR markup has painted, flashing the wrong theme.
 */
it('keeps the theme initialisation script out of Rocket Loader', function (): void {
    $html = (string) view('app', ['page' => ['component' => 'Public/HomePage', 'props' => []]]);

    expect($html)->toMatch('/<script data-cfasync="false">\s*\(function\(\) \{\s*let savedTheme/');
});
