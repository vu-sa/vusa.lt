<?php

use Illuminate\Support\Arr;

test('every translation key referenced by the page resolves in lt and en', function (): void {
    $page = file_get_contents(resource_path('js/Pages/Admin/Settings/EditAtstovavimasSettings.vue'));
    preg_match_all("/\\\$t\('([a-z0-9_.]+)'/", $page, $matches);

    $keys = $matches[1];
    expect($keys)->not->toBeEmpty();

    foreach (['lt', 'en'] as $locale) {
        $translations = require lang_path("admin/{$locale}/settings.php");

        foreach ($keys as $key) {
            // Keys are prefixed with the file name ("settings."), Arr::get expects the path inside it.
            $path = substr($key, strlen('settings.'));
            expect(Arr::get($translations, $path))->not->toBeNull("Key [{$key}] missing in {$locale}");
        }
    }
});
