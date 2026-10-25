<?php

test('route generation succeeds without a Typesense API key', function (): void {
    config(['scout.typesense.client-settings.api_key' => null]);
    $path = tempnam(storage_path('framework'), 'ziggy-');
    $output = $path.'.js';

    try {
        $this->artisan('ziggy:generate', ['path' => 'storage/framework/'.basename($output)])
            ->assertSuccessful();

        expect(file_get_contents($output))->toContain('const Ziggy =');
    } finally {
        @unlink($output);
        unlink($path);
    }
});
