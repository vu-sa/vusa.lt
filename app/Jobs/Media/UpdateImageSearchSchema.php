<?php

namespace App\Jobs\Media;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

class UpdateImageSearchSchema implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        if (app()->environment('testing')) {
            return;
        }
        throw_if(app()->environment('staging') && config('scout.prefix') !== 'staging_', new \RuntimeException('Staging search prefix is not isolated'));
        $client = new Client(config('scout.typesense.client-settings'));
        foreach (config('scout.typesense.model-settings') as $model => $settings) {
            $fields = array_filter($settings['collection-schema']['fields'], fn (array $field): bool => preg_match('/_(display_url|srcset|width|height|focal_point)$/', $field['name']) === 1);
            if ($fields === []) {
                continue;
            }
            $name = (new $model)->searchableAs();
            try {
                $schema = $client->collections[$name]->retrieve();
                $existing = array_column($schema['fields'], 'name');
                $missing = array_values(array_filter($fields, fn (array $field): bool => ! in_array($field['name'], $existing, true)));
                if ($missing !== []) {
                    $client->collections[$name]->update(['fields' => $missing]);
                }
            } catch (ObjectNotFound) {
                // A fresh installation creates the collection on its first import.
            }
        }
    }
}
