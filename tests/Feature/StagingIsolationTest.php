<?php

use App\Http\Middleware\StagingReadOnlyMode;
use App\Listeners\BlockExternalNotificationsOnStaging;
use App\Notifications\ReminderToLoginNotification;
use App\Services\MediaLibrary\StagingAwareFileRemover;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $connection = (string) config('database.default');
    $keys = [
        'app.env',
        'app.staging_user',
        'app.staging_password',
        'app.files_read_only',
        'app.sharepoint_read_only',
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
        'broadcasting.default',
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
        'broadcasting.default' => 'null',
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

test('staging basic auth fails closed when credentials are missing', function (): void {
    config([
        'app.env' => 'staging',
        'app.staging_user' => null,
        'app.staging_password' => null,
    ]);

    $this->get('/login')->assertServiceUnavailable();

    $this->get('/up')->assertOk();
});

test('read only middleware blocks the real file and SharePoint mutation route names', function (string $routeName): void {
    configureSafeStagingIsolation();

    $request = Request::create('/test', 'POST', server: ['HTTP_ACCEPT' => 'application/json']);
    $route = (new Route('POST', '/test', fn () => null))->name($routeName);
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

test('only database notification channels are allowed in staging', function (): void {
    $listener = new BlockExternalNotificationsOnStaging;
    $notification = new ReminderToLoginNotification;

    config(['app.env' => 'staging']);

    expect($listener->handle(new NotificationSending(new stdClass, $notification, 'database')))->toBeNull()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, 'mail')))->toBeFalse()
        ->and($listener->handle(new NotificationSending(new stdClass, $notification, 'broadcast')))->toBeFalse();

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
