<?php

namespace App\Services;

use App\Enums\SystemMaintenanceAction;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SystemMaintenanceService
{
    /**
     * Every tag the public site caches content under.
     */
    public const PUBLIC_CONTENT_CACHE_TAGS = [
        'homepage', 'navigation', 'banners', 'quick_links', 'pages',
        'news', 'calendar', 'documents', 'sitemap', 'schemas',
    ];

    /**
     * @return bool false only when a synchronous action reported failure
     */
    public function run(SystemMaintenanceAction $action, User $user): bool
    {
        $succeeded = match ($action) {
            SystemMaintenanceAction::RefreshPublicContent => $this->refreshPublicContent(),
            SystemMaintenanceAction::ClearApplicationCache => $this->clearApplicationCache(),
            SystemMaintenanceAction::RestartQueueWorkers => Artisan::call('queue:restart') === Command::SUCCESS,
            SystemMaintenanceAction::SendTestMail => Artisan::call('mail:test', ['email' => $user->email]) === Command::SUCCESS,
            default => $this->queue($action),
        };

        activity()
            ->causedBy($user)
            ->event('system_maintenance')
            ->withProperties(['action' => $action->value, 'succeeded' => $succeeded])
            ->log('system_maintenance');

        return $succeeded;
    }

    private function refreshPublicContent(): bool
    {
        Cache::tags(self::PUBLIC_CONTENT_CACHE_TAGS)->flush();

        foreach (HandleInertiaRequests::SHARED_CACHE_KEYS as $key) {
            Cache::forget($key);
        }

        NavigationService::clearCache();
        IcalendarService::clearCache();

        return true;
    }

    private function clearApplicationCache(): bool
    {
        // Otherwise the status page reports the scheduler dead until its next minute tick.
        $heartbeat = Cache::get(SystemMonitorService::HEARTBEAT_CACHE_KEY);

        $exitCode = Artisan::call('cache:clear');

        if ($heartbeat !== null) {
            Cache::forever(SystemMonitorService::HEARTBEAT_CACHE_KEY, $heartbeat);
        }

        return $exitCode === Command::SUCCESS;
    }

    private function queue(SystemMaintenanceAction $action): bool
    {
        Artisan::queue((string) $action->command());

        return true;
    }
}
