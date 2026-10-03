<?php

namespace App\Enums;

/**
 * The only operations the System status page may trigger — the request carries one of these
 * values, never a command name.
 */
enum SystemMaintenanceAction: string
{
    case RefreshPublicContent = 'refresh-public-content';
    case ClearApplicationCache = 'clear-application-cache';
    case RestartQueueWorkers = 'restart-queue-workers';
    case SendTestMail = 'send-test-mail';
    case SyncPublicSearch = 'sync-public-search';
    case RefreshInstitutionActivity = 'refresh-institution-activity';
    case SyncSharepointDocuments = 'sync-sharepoint-documents';
    case ReindexSearch = 'reindex-search';

    /**
     * The Artisan command behind a queued action.
     */
    public function command(): ?string
    {
        return match ($this) {
            self::SyncPublicSearch => 'search:sync-public',
            self::RefreshInstitutionActivity => 'institutions:refresh-activity-status',
            self::SyncSharepointDocuments => 'sharepoint:sync-documents',
            self::ReindexSearch => 'search:reindex',
            default => null,
        };
    }

    /**
     * Long-running actions go to the queue so the request returns before a PHP timeout.
     */
    public function isQueued(): bool
    {
        return $this->command() !== null;
    }

    /**
     * Visibly disrupts users while it runs, so the UI warns before confirming.
     */
    public function isDisruptive(): bool
    {
        return in_array($this, [self::ClearApplicationCache, self::ReindexSearch], true);
    }
}
