<?php

namespace App\Services;

class StagingIsolationService
{
    /**
     * @return list<string>
     */
    public function errors(): array
    {
        if (config('app.env') !== 'staging') {
            return [];
        }

        return array_values(array_filter([
            ...$this->databaseErrors(),
            $this->missing(config('app.staging_user')) ? 'STAGING_USER must be set.' : null,
            $this->missing(config('app.staging_password')) ? 'STAGING_PASSWORD must be set.' : null,
            config('database.redis.default.database') !== '2' ? 'REDIS_DB must be 2.' : null,
            config('database.redis.cache.database') !== '3' ? 'REDIS_CACHE_DB must be 3.' : null,
            config('database.redis.options.prefix') !== 'vusa_staging_' ? 'REDIS_PREFIX must be vusa_staging_.' : null,
            config('cache.prefix') !== 'vusa_staging_cache_' ? 'CACHE_PREFIX must be vusa_staging_cache_.' : null,
            config('queue.connections.redis.queue') !== 'staging' ? 'REDIS_QUEUE must be staging.' : null,
            config('scout.prefix') !== 'staging_' ? 'SCOUT_PREFIX must be staging_.' : null,
            config('app.files_read_only') !== true ? 'FILES_READ_ONLY must be true.' : null,
            ...$this->sharepointErrors(),
            config('mail.default') !== 'log' ? 'The staging mailer must be log.' : null,
            config('broadcasting.default') !== 'null' ? 'The staging broadcaster must be null.' : null,
            ! $this->missing(config('webpush.vapid.public_key')) ? 'VAPID_PUBLIC_KEY must be empty.' : null,
            ! $this->missing(config('webpush.vapid.private_key')) ? 'VAPID_PRIVATE_KEY must be empty.' : null,
            ! $this->missing(config('services.umami.website_id')) ? 'UMAMI_WEBSITE_ID must be empty.' : null,
        ]));
    }

    /**
     * @return list<string>
     */
    public function databaseErrors(): array
    {
        if (config('app.env') !== 'staging') {
            return [];
        }

        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $expectedDatabase = config('app.staging_refresh.expected_database');
        $expectedUsername = config('app.staging_refresh.expected_database_username');

        return array_values(array_filter([
            $this->missing($expectedDatabase) ? 'STAGING_EXPECTED_DATABASE must be set.' : null,
            $this->missing($expectedUsername) ? 'STAGING_EXPECTED_DB_USERNAME must be set.' : null,
            ! $this->missing($expectedDatabase) && $database !== $expectedDatabase
                ? 'DB_DATABASE does not match STAGING_EXPECTED_DATABASE.'
                : null,
            ! $this->missing($expectedUsername) && $username !== $expectedUsername
                ? 'DB_USERNAME does not match STAGING_EXPECTED_DB_USERNAME.'
                : null,
        ]));
    }

    /**
     * A writable staging SharePoint must use its own Entra app and an allowlisted, non-production site.
     *
     * @return list<string>
     */
    public function sharepointErrors(): array
    {
        if (config('app.env') !== 'staging' || config('app.sharepoint_read_only') === true) {
            return [];
        }

        $sharepoint = config('filesystems.sharepoint');
        $writableSiteIds = $sharepoint['writable_site_ids'] ?? [];
        $production = $sharepoint['production'];

        return array_values(array_filter([
            in_array($sharepoint['client_id'], [null, '', $production['client_id']], true)
                ? 'SHAREPOINT_CLIENT_ID must be the staging Entra app, not production.'
                : null,
            $writableSiteIds === [] ? 'SHAREPOINT_WRITABLE_SITE_IDS must be set.' : null,
            array_intersect($writableSiteIds, $production['site_ids']) !== []
                ? 'SHAREPOINT_WRITABLE_SITE_IDS must not contain a production site.'
                : null,
            ! in_array($sharepoint['site_id'], $writableSiteIds, true)
                ? 'SHAREPOINT_SITE_ID must be listed in SHAREPOINT_WRITABLE_SITE_IDS.'
                : null,
            in_array($sharepoint['vusa_drive_id'], $production['drive_ids'], true)
                ? 'SHAREPOINT_VUSA_DRIVE_ID must not be a production drive.'
                : null,
        ]));
    }

    private function missing(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
