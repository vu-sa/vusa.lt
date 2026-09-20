<?php

use App\Models\DailyDeviceMetric;
use App\Models\Tenant;
use App\Services\DeviceMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(DeviceMetricService::class);
});

describe('DeviceMetricService: User-Agent Classification', function (): void {
    test('classifies desktop user agents', function (): void {
        $windowsChrome = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $macSafari = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';
        $linuxFirefox = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0';

        expect($this->service->classifyUserAgent($windowsChrome))->toBe('desktop')
            ->and($this->service->classifyUserAgent($macSafari))->toBe('desktop')
            ->and($this->service->classifyUserAgent($linuxFirefox))->toBe('desktop')
            ->and($this->service->classifyUserAgent(null))->toBe('desktop')
            ->and($this->service->classifyUserAgent(''))->toBe('desktop');
    });

    test('classifies phone user agents', function (): void {
        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $androidPhone = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
        $windowsPhone = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows Phone 8.0; Trident/6.0; IEMobile/10.0; ARM; Touch)';

        expect($this->service->classifyUserAgent($iphone))->toBe('phone')
            ->and($this->service->classifyUserAgent($androidPhone))->toBe('phone')
            ->and($this->service->classifyUserAgent($windowsPhone))->toBe('phone');
    });

    test('classifies tablet user agents', function (): void {
        $ipad = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
        $androidTablet = 'Mozilla/5.0 (Linux; Android 13; SM-X900) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        expect($this->service->classifyUserAgent($ipad))->toBe('tablet')
            ->and($this->service->classifyUserAgent($androidTablet))->toBe('tablet');
    });
});

describe('DeviceMetricService: Recording & Metrics', function (): void {
    test('records logins per device type for today', function (): void {
        $desktopUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $phoneUA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)';
        $tabletUA = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)';

        $this->service->recordLogin($desktopUA);
        $this->service->recordLogin($desktopUA);
        $this->service->recordLogin($phoneUA);
        $this->service->recordLogin($tabletUA);

        $metric = DailyDeviceMetric::where('date', today()->toDateString())->first();

        expect($metric)->not->toBeNull()
            ->and($metric->desktop_logins)->toBe(2)
            ->and($metric->phone_logins)->toBe(1)
            ->and($metric->tablet_logins)->toBe(1)
            ->and($metric->pwa_launches)->toBe(0);
    });

    test('records PWA launches for today', function (): void {
        $this->service->recordPwaLaunch();
        $this->service->recordPwaLaunch();

        $metric = DailyDeviceMetric::where('date', today()->toDateString())->first();

        expect($metric)->not->toBeNull()
            ->and($metric->pwa_launches)->toBe(2);
    });

    test('getRecentMetrics returns summary with accurate percentages and records', function (): void {
        DailyDeviceMetric::create([
            'date' => today()->subDays(1)->toDateString(),
            'desktop_logins' => 70,
            'phone_logins' => 20,
            'tablet_logins' => 10,
            'pwa_launches' => 5,
        ]);

        DailyDeviceMetric::create([
            'date' => today()->toDateString(),
            'desktop_logins' => 30,
            'phone_logins' => 10,
            'tablet_logins' => 0,
            'pwa_launches' => 2,
        ]);

        $metrics = $this->service->getRecentMetrics(30);

        expect($metrics['records'])->toHaveCount(2)
            ->and($metrics['summary']['total_logins'])->toBe(140)
            ->and($metrics['summary']['total_desktop'])->toBe(100)
            ->and($metrics['summary']['total_phone'])->toBe(30)
            ->and($metrics['summary']['total_tablet'])->toBe(10)
            ->and($metrics['summary']['total_pwa_launches'])->toBe(7)
            ->and($metrics['summary']['desktop_percentage'])->toBe(71.4)
            ->and($metrics['summary']['phone_percentage'])->toBe(21.4)
            ->and($metrics['summary']['tablet_percentage'])->toBe(7.1);
    });
});

describe('RecordDeviceLogin Listener & Privacy', function (): void {
    test('Login event fires RecordDeviceLogin listener', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);

        event(new Illuminate\Auth\Events\Login('web', $user, false));

        $metric = DailyDeviceMetric::where('date', today()->toDateString())->first();
        expect($metric)->not->toBeNull()
            ->and($metric->desktop_logins + $metric->phone_logins + $metric->tablet_logins)->toBeGreaterThanOrEqual(1);
    });

    test('daily_device_metrics schema does not store user_id or IP address (U26 privacy)', function (): void {
        $columns = Schema::getColumnListing('daily_device_metrics');

        expect($columns)->not->toContain('user_id')
            ->and($columns)->not->toContain('user')
            ->and($columns)->not->toContain('ip_address')
            ->and($columns)->not->toContain('ip')
            ->and($columns)->not->toContain('fingerprint');
    });
});

describe('PWA Launch Tracking in Middleware', function (): void {
    test('request with ?source=pwa records a launch once per session', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);

        // First visit with source=pwa in session
        $this->actingAs($user)->get('/mano?source=pwa');

        $metric = DailyDeviceMetric::where('date', today()->toDateString())->first();
        expect($metric)->not->toBeNull()
            ->and($metric->pwa_launches)->toBe(1);

        // Second visit within the same session
        $this->get('/mano?source=pwa');

        $metric->refresh();
        expect($metric->pwa_launches)->toBe(1);
    });

    test('request with pwa_mode=1 cookie on /mano records launch once per session', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);

        $this->actingAs($user)
            ->withUnencryptedCookie('pwa_mode', '1')
            ->get('/mano');

        $metric = DailyDeviceMetric::where('date', today()->toDateString())->first();
        expect($metric)->not->toBeNull()
            ->and($metric->pwa_launches)->toBe(1);

        // Second visit in same session
        $this->get('/mano');

        $metric->refresh();
        expect($metric->pwa_launches)->toBe(1);
    });
});
