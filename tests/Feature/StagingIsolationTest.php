<?php

use App\Http\Middleware\StagingReadOnlyMode;
use App\Listeners\BlockExternalNotificationsOnStaging;
use App\Notifications\WelcomeNotification;
use App\Services\MediaLibrary\StagingAwareFileRemover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\WebPushChannel;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $connection = (string) config('database.default');
    $keys = [
        'app.env',
        'app.staging_user',
        'app.staging_password',
        'app.staging_basic_auth_enabled',
        'app.files_read_only',
        'app.sharepoint_read_only',
        'filesystems.sharepoint.client_id',
        'filesystems.sharepoint.site_id',
        'filesystems.sharepoint.vusa_drive_id',
        'filesystems.sharepoint.writable_site_ids',
        'app.staging_refresh.expected_database',
        'app.staging_refresh.expected_database_username',
        "database.connections.{$connection}.database",
        "database.connections.{$connection}.username",
        'database.redis.default.database',
        'database.redis.cache.database',
        'database.redis.options.prefix',
        'cache.prefix',
        'queue.connections.redis.queue',
        'scout.prefix',
        'mail.default',
        'app.staging_broadcasting_enabled',
        'app.staging_push_enabled',
        'broadcasting.default',
        'broadcasting.connections.reverb.options.host',
        'broadcasting.connections.reverb.options.port',
        'reverb.servers.reverb.port',
        'webpush.vapid.subject',
        'webpush.vapid.public_key',
        'webpush.vapid.private_key',
        'services.umami.website_id',
    ];

    $this->stagingIsolationConfig = collect($keys)
        ->mapWithKeys(fn (string $key): array => [$key => config($key)])
        ->all();
});

afterEach(function (): void {
    config($this->stagingIsolationConfig);
});

function configureSafeStagingIsolation(): void
{
    $connection = (string) config('database.default');

    config([
        'app.env' => 'staging',
        'app.staging_user' => 'reviewer',
        'app.staging_password' => 'secret',
        'app.staging_basic_auth_enabled' => true,
        'app.files_read_only' => true,
        'app.sharepoint_read_only' => true,
        'app.staging_refresh.expected_database' => 'staging_database',
        'app.staging_refresh.expected_database_username' => 'staging_user',
        "database.connections.{$connection}.database" => 'staging_database',
        "database.connections.{$connection}.username" => 'staging_user',
        'database.redis.default.database' => '2',
        'database.redis.cache.database' => '3',
        'database.redis.options.prefix' => 'vusa_staging_',
        'cache.prefix' => 'vusa_staging_cache_',
        'queue.connections.redis.queue' => 'staging',
        'scout.prefix' => 'staging_',
        'mail.default' => 'log',
        'app.staging_broadcasting_enabled' => false,
        'app.staging_push_enabled' => false,
        'broadcasting.default' => 'null',
        'webpush.vapid.subject' => null,
        'webpush.vapid.public_key' => null,
        'webpush.vapid.private_key' => null,
        'services.umami.website_id' => null,
    ]);
}

test('the staging isolation command accepts a safe configuration', function (): void {
    configureSafeStagingIsolation();

    $this->artisan('staging:verify-isolation')
        ->expectsOutputToContain('configuration is safe')
        ->assertExitCode(0);
});

test('the staging isolation command reports every unsafe boundary', function (): void {
    configureSafeStagingIsolation();

    config([
        'app.staging_user' => null,
        'database.redis.default.database' => '0',
        'scout.prefix' => '',
        'mail.default' => 'smtp',
        'services.umami.website_id' => 'production-site',
    ]);

    $this->artisan('staging:verify-isolation')
        ->expectsOutputToContain('STAGING_USER must be set')
        ->expectsOutputToContain('REDIS_DB must be 2')
        ->expectsOutputToContain('SCOUT_PREFIX must be staging_')
        ->expectsOutputToContain('staging mailer must be log')
        ->expectsOutputToContain('UMAMI_WEBSITE_ID must be empty')
        ->assertExitCode(1);
});

test('a writable staging SharePoint accepts its own app and an allowlisted test site', function (): void {
    configureSafeStagingIsolation();

    config([
        'app.sharepoint_read_only' => false,
        'filesystems.sharepoint.client_id' => 'staging-app',
        'filesystems.sharepoint.site_id' => 'test-site',
        'filesystems.sharepoint.vusa_drive_id' => 'test-drive',
        'filesystems.sharepoint.writable_site_ids' => ['test-site'],
    ]);

    $this->artisan('staging:verify-isolation')
        ->expectsOutputToContain('configuration is safe')
        ->assertExitCode(0);
});

test('a writable staging SharePoint refuses production identifiers', function (): void {
    configureSafeStagingIsolation();
    $production = config('filesystems.sharepoint.production');

    config([
        'app.sharepoint_read_only' => false,
        'filesystems.sharepoint.client_id' => $production['client_id'],
        'filesystems.sharepoint.site_id' => 'unlisted-site',
        'filesystems.sharepoint.vusa_drive_id' => $production['drive_ids'][0],
        'filesystems.sharepoint.writable_site_ids' => $production['site_ids'],
    ]);

    $this->artisan('staging:verify-isolation')
        ->expectsOutputToContain('SHAREPOINT_CLIENT_ID must be the staging Entra app')
        ->expectsOutputToContain('SHAREPOINT_WRITABLE_SITE_IDS must not contain a production site')
        ->expectsOutputToContain('SHAREPOINT_SITE_ID must be listed in SHAREPOINT_WRITABLE_SITE_IDS')
        ->expectsOutputToContain('SHAREPOINT_VUSA_DRIVE_ID must not be a production drive')
        ->assertExitCode(1);
});

test('staging broadcasting and push accept their own Reverb process and VAPID keys', function (): void {
    configureSafeStagingIsolation();

    config([
        'app.staging_broadcasting_enabled' => true,
        'app.staging_push_enabled' => true,
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => '6002',
        'reverb.servers.reverb.port' => '6002',
        'webpush.vapid.subject' => 'mailto:it@vusa.lt',
        'webpush.vapid.public_key' => 'staging-public',
        'webpush.vapid.private_key' => 'staging-private',
    ]);

    $this->artisan('staging:verify-isolation')
        ->expectsOutputToContain('configuration is safe')
        ->assertExitCode(0);
});

test('staging broadcasting refuses production Reverb and push refuses missing keys', function (): void {
    configureSafeStagingIsolation();

    config([
        'app.staging_broadcasting_enabled' => true,
        'app.staging_push_enabled' => true,
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.options.host' => 'www.vusa.lt',
        'broadcasting.connections.reverb.options.port' => config('broadcasting.production.reverb_port'),
        'reverb.servers.reverb.port' => '6002',
        'webpush.vapid.public_key' => 'staging-public',
    ]);

    $this->artisan('staging:verify-isolation')
        ->expectsOutputToContain('REVERB_HOST must be 127.0.0.1')
        ->expectsOutputToContain('REVERB_PORT must not be production Reverb\'s port')
        ->expectsOutputToContain('REVERB_SERVER_PORT must match REVERB_PORT')
        ->expectsOutputToContain('VAPID_SUBJECT must be set when STAGING_PUSH_ENABLED=true')
        ->expectsOutputToContain('VAPID_PRIVATE_KEY must be set when STAGING_PUSH_ENABLED=true')
        ->assertExitCode(1);
});

test('staging basic auth fails closed when credentials are missing', function (): void {
    config([
        'app.env' => 'staging',
        'app.staging_user' => null,
        'app.staging_password' => null,
    ]);

    $this->get('/login')->assertServiceUnavailable();

    $this->get('/up')->assertOk();
});

test('staging basic auth requires valid credentials while enabled', function (): void {
    configureSafeStagingIsolation();

    $this->get('/login')->assertUnauthorized();
    $this->withHeader('Authorization', 'Basic '.base64_encode('reviewer:wrong'))
        ->get('/login')->assertUnauthorized();
    $this->withHeader('Authorization', 'Basic '.base64_encode('reviewer:secret'))
        ->get('/login')->assertOk();
});

test('disabling staging basic auth leaves application login in place', function (): void {
    configureSafeStagingIsolation();
    config(['app.staging_basic_auth_enabled' => false]);

    $this->get('/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('/mano')->assertRedirect(route('login'));
});

test('staging access settings do not affect local login', function (): void {
    config([
        'app.env' => 'local',
        'app.staging_user' => null,
        'app.staging_password' => null,
    ]);

    $this->get('/login')->assertOk()->assertHeaderMissing('X-Robots-Tag');
});

test('staging robots rules remain available when the password gate is off', function (): void {
    configureSafeStagingIsolation();
    config(['app.staging_basic_auth_enabled' => false]);

    $this->get('https://www.naujas.vusa.lt/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /');
});

test('read only middleware blocks the real file and SharePoint mutation route names', function (string $routeName): void {
    configureSafeStagingIsolation();

    $request = Request::create('/test', 'POST', server: ['HTTP_ACCEPT' => 'application/json']);
    $route = new Route('POST', '/test', fn () => null)->name($routeName);
    $request->setRouteResolver(fn () => $route);

    $response = app(StagingReadOnlyMode::class)
        ->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('STAGING_READ_ONLY');
})->with([
    'admin file upload' => 'files.store',
    'API file upload' => 'api.v1.admin.files.store',
    'SharePoint folder creation' => 'sharepoint.createFolder',
    'SharePoint public permission creation' => 'sharepoint.createPublicPermission',
    'SharePoint file deletion' => 'fileableFiles.destroy',
]);

test('only database notification channels are allowed in staging by default', function (): void {
    $listener = new BlockExternalNotificationsOnStaging;
    $notification = new WelcomeNotification;

    config(['app.env' => 'staging', 'app.staging_broadcasting_enabled' => false, 'app.staging_push_enabled' => false]);

    expect($listener->handle(new NotificationSending(new stdClass, $notification, 'database')))->toBeNull()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, 'mail')))->toBeFalse()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, 'broadcast')))->toBeFalse()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, WebPushChannel::class)))->toBeFalse();

    config(['app.staging_broadcasting_enabled' => true, 'app.staging_push_enabled' => true]);

    expect($listener->handle(new NotificationSending(new stdClass, $notification, 'broadcast')))->toBeNull()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, WebPushChannel::class)))->toBeNull()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, 'mail')))->toBeFalse();

    config(['app.env' => 'production']);

    expect($listener->handle(new NotificationSending(new stdClass, $notification, 'mail')))->toBeNull();
});

test('Media Library preserves shared files when staging deletes local media records', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('media/shared.jpg', 'shared');
    configureSafeStagingIsolation();

    app(StagingAwareFileRemover::class)->removeFile('media/shared.jpg', 'public');

    Storage::disk('public')->assertExists('media/shared.jpg');
});
