<?php

namespace App\Listeners;

use App\Services\RepStatisticsCache;
use App\Settings\AtstovavimasSettings;
use App\Settings\MeetingSettings;
use App\Settings\RepMetricSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

class InvalidateRepStatisticsSettings
{
    public function handle(SettingsSaved $event): void
    {
        if ($event->settings instanceof MeetingSettings || $event->settings instanceof AtstovavimasSettings || $event->settings instanceof RepMetricSettings) {
            RepStatisticsCache::invalidate();
        }
    }
}
