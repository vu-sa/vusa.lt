<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Where SharePoint document discovery is in the archive's change feed. Written only by discovery.
 */
class DocumentDiscoverySettings extends Settings
{
    /** Graph deltaLink of the archive drive; null forces a full listing on the next run. */
    public ?string $delta_link = null;

    /** ISO 8601 time of the last successful run. */
    public ?string $last_run_at = null;

    /** ISO 8601 time of the last successful full listing; null until the first one (the baseline). */
    public ?string $last_full_run_at = null;

    /** Files left out after failing several runs in a row; they wait for a change or the next full listing. */
    public int $skipped_files = 0;

    public static function group(): string
    {
        return 'document_discovery';
    }
}
