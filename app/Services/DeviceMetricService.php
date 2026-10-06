<?php

namespace App\Services;

use App\Models\DailyDeviceMetric;
use Illuminate\Support\Collection;

class DeviceMetricService
{
    /**
     * Classify a User-Agent string into a coarse device category.
     *
     * Returns 'tablet', 'phone', or 'desktop'.
     */
    public function classifyUserAgent(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'desktop';
        }

        $ua = strtolower($userAgent);

        // iPad, Android tablet (Android without Mobile), or explicit Tablet token
        if (
            str_contains($ua, 'ipad')
            || (str_contains($ua, 'android') && ! str_contains($ua, 'mobile'))
            || str_contains($ua, 'tablet')
        ) {
            return 'tablet';
        }

        // Phone checks
        if (
            str_contains($ua, 'iphone')
            || str_contains($ua, 'ipod')
            || (str_contains($ua, 'android') && str_contains($ua, 'mobile'))
            || str_contains($ua, 'mobile')
            || str_contains($ua, 'windows phone')
            || str_contains($ua, 'blackberry')
            || str_contains($ua, 'webos')
        ) {
            return 'phone';
        }

        return 'desktop';
    }

    /**
     * Record a login event for the current day, categorized by device type.
     */
    public function recordLogin(?string $userAgent): void
    {
        $device = $this->classifyUserAgent($userAgent);

        $column = match ($device) {
            'phone' => 'phone_logins',
            'tablet' => 'tablet_logins',
            default => 'desktop_logins',
        };

        $metric = DailyDeviceMetric::firstOrCreate(
            ['date' => today()->toDateString()],
            [
                'phone_logins' => 0,
                'tablet_logins' => 0,
                'desktop_logins' => 0,
                'pwa_launches' => 0,
            ]
        );

        $metric->increment($column);
    }

    /**
     * Record a PWA launch for the current day.
     */
    public function recordPwaLaunch(): void
    {
        $metric = DailyDeviceMetric::firstOrCreate(
            ['date' => today()->toDateString()],
            [
                'phone_logins' => 0,
                'tablet_logins' => 0,
                'desktop_logins' => 0,
                'pwa_launches' => 0,
            ]
        );

        $metric->increment('pwa_launches');
    }

    /**
     * Get device metrics over the last N days with summary statistics.
     *
     * @return array{
     *     records: Collection<int, array{date: non-falsy-string, phone_logins: int, tablet_logins: int, desktop_logins: int, pwa_launches: int, total_logins: int}>,
     *     summary: array{days: int, total_logins: int, total_phone: int, total_tablet: int, total_desktop: int, total_pwa_launches: int, phone_percentage: float, tablet_percentage: float, desktop_percentage: float}
     * }
     */
    public function getRecentMetrics(int $days = 30): array
    {
        $startDate = today()->subDays($days - 1)->toDateString();

        $metrics = DailyDeviceMetric::query()
            ->where('date', '>=', $startDate)
            ->orderByDesc('date')
            ->get();

        $totalPhone = (int) $metrics->sum('phone_logins');
        $totalTablet = (int) $metrics->sum('tablet_logins');
        $totalDesktop = (int) $metrics->sum('desktop_logins');
        $totalPwaLaunches = (int) $metrics->sum('pwa_launches');
        $totalLogins = $totalPhone + $totalTablet + $totalDesktop;

        $phonePercentage = $totalLogins > 0 ? round(($totalPhone / $totalLogins) * 100, 1) : 0.0;
        $tabletPercentage = $totalLogins > 0 ? round(($totalTablet / $totalLogins) * 100, 1) : 0.0;
        $desktopPercentage = $totalLogins > 0 ? round(($totalDesktop / $totalLogins) * 100, 1) : 0.0;

        $records = $metrics->map(fn (DailyDeviceMetric $m) => [
            'date' => $m->date->format('Y-m-d'),
            'phone_logins' => $m->phone_logins,
            'tablet_logins' => $m->tablet_logins,
            'desktop_logins' => $m->desktop_logins,
            'pwa_launches' => $m->pwa_launches,
            'total_logins' => $m->phone_logins + $m->tablet_logins + $m->desktop_logins,
        ]);

        return [
            'records' => $records,
            'summary' => [
                'days' => $days,
                'total_logins' => $totalLogins,
                'total_phone' => $totalPhone,
                'total_tablet' => $totalTablet,
                'total_desktop' => $totalDesktop,
                'total_pwa_launches' => $totalPwaLaunches,
                'phone_percentage' => $phonePercentage,
                'tablet_percentage' => $tabletPercentage,
                'desktop_percentage' => $desktopPercentage,
            ],
        ];
    }
}
