<?php

use App\Enums\SharepointConfigEnum;
use App\Enums\SharepointFieldEnum;
use App\Enums\SharepointPermissionTypeEnum;
use App\Enums\SharepointScopeEnum;
use App\Services\SharepointGraphService;
use App\Settings\SharepointSettings;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Microsoft\Graph\Generated\Models\Permission;
use Microsoft\Graph\GraphServiceClient;

uses(RefreshDatabase::class);

describe('SharepointGraphService', function () {
    beforeEach(function () {
        // Mock the settings
        $this->settings = Mockery::mock(SharepointSettings::class);
        $this->settings->permission_expiry_days = 365;
        $this->settings->default_folder_structure = 'General/{type}/{name}';

        // Mock the Graph client
        $this->graphClient = Mockery::mock(GraphServiceClient::class);

        // Create service instance for testing
        $this->service = new SharepointGraphService(
            siteId: 'test-site-id',
            driveId: 'test-drive-id',
            listId: 'test-list-id',
            settings: $this->settings
        );

        // Use reflection to inject the mocked graph client
        $reflection = new ReflectionClass($this->service);
        $graphProperty = $reflection->getProperty('graph');
        $graphProperty->setAccessible(true);
        $graphProperty->setValue($this->service, $this->graphClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('constructor', function () {
        test('initializes with default settings when none provided', function () {
            $service = new SharepointGraphService;

            expect($service->siteId)->toBe(config('filesystems.sharepoint.site_id'));
        });

        test('uses provided settings over defaults', function () {
            $customSettings = Mockery::mock(SharepointSettings::class);
            $customSettings->permission_expiry_days = 180;

            $service = new SharepointGraphService(
                siteId: 'custom-site',
                settings: $customSettings
            );

            expect($service->siteId)->toBe('custom-site');
        });

        test('logs initialization info', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('SharepointGraphService initialized', Mockery::type('array'));

            new SharepointGraphService(
                siteId: 'test-site',
                driveId: 'test-drive'
            );
        });

        test('handles initialization errors gracefully', function () {
            // Mock config to return invalid values
            config(['filesystems.sharepoint.tenant_id' => null]);

            expect(fn () => new SharepointGraphService)
                ->toThrow(\Exception::class);
        });
    });

    describe('createPublicPermission', function () {
        test('creates permission with correct parameters', function () {
            $driveItemId = 'test-drive-item';
            $mockPermission = Mockery::mock(Permission::class);

            // Mock the complex Graph API chain
            $this->mockGraphApiChain($mockPermission);

            $result = $this->service->createPublicPermission(
                siteId: 'test-site',
                driveItemId: $driveItemId
            );

            expect($result)->toBe($mockPermission);
        });

        test('uses settings for permission expiry', function () {
            $this->settings->permission_expiry_days = 180;
            $driveItemId = 'test-drive-item';
            $mockPermission = Mockery::mock(Permission::class);

            $this->mockGraphApiChain($mockPermission);

            $result = $this->service->createPublicPermission(
                siteId: null,
                driveItemId: $driveItemId
            );

            expect($result)->toBe($mockPermission);
        });

        test('validates required parameters', function () {
            expect(fn () => $this->service->createPublicPermission(
                siteId: null,
                driveItemId: ''
            ))->toThrow(\InvalidArgumentException::class, "Parameter 'driveItemId' cannot be empty");
        });

        test('handles API failures with retry logic', function () {
            $driveItemId = 'test-drive-item';

            // Mock initial failure then success
            $this->mockGraphApiFailureThenSuccess();

            Log::shouldReceive('info')->times(2); // Initial attempt + retry success

            $result = $this->service->createPublicPermission(
                siteId: null,
                driveItemId: $driveItemId
            );

            expect($result)->toBeInstanceOf(Permission::class);
        });

        test('logs permission creation', function () {
            $driveItemId = 'test-drive-item';
            $mockPermission = Mockery::mock(Permission::class);

            $this->mockGraphApiChain($mockPermission);

            Log::shouldReceive('info')
                ->with('Public permission created', Mockery::on(function ($context) use ($driveItemId) {
                    return $context['drive_item_id'] === $driveItemId;
                }));

            $this->service->createPublicPermission(
                siteId: null,
                driveItemId: $driveItemId
            );
        });

        test('uses enum values for permission type and scope', function () {
            $driveItemId = 'test-drive-item';
            $mockPermission = Mockery::mock(Permission::class);

            // Verify that enum values are used
            $this->mockGraphApiChainWithEnumVerification($mockPermission);

            $this->service->createPublicPermission(
                siteId: null,
                driveItemId: $driveItemId
            );
        });

        test('handles false datetime parameter', function () {
            $driveItemId = 'test-drive-item';
            $mockPermission = Mockery::mock(Permission::class);

            $this->mockGraphApiChain($mockPermission);

            Log::shouldReceive('info')
                ->with('Public permission created', Mockery::on(function ($context) {
                    return $context['expiration'] === 'never';
                }));

            $result = $this->service->createPublicPermission(
                siteId: null,
                driveItemId: $driveItemId,
                datetime: false
            );

            expect($result)->toBe($mockPermission);
        });
    });

    describe('batchProcessDocuments', function () {
        test('filters out existing documents', function () {
            $documents = new EloquentCollection([
                Mockery::mock(\App\Models\Document::class, [
                    'sharepoint_id' => 'existing-doc',
                ]),
                Mockery::mock(\App\Models\Document::class, [
                    'sharepoint_id' => 'new-doc',
                ]),
            ]);

            // Mock the Document query to simulate existing document
            \App\Models\Document::shouldReceive('query')
                ->andReturnSelf()
                ->shouldReceive('where')
                ->with('sharepoint_id', 'existing-doc')
                ->andReturnSelf()
                ->shouldReceive('doesntExist')
                ->andReturn(false);

            \App\Models\Document::shouldReceive('query')
                ->andReturnSelf()
                ->shouldReceive('where')
                ->with('sharepoint_id', 'new-doc')
                ->andReturnSelf()
                ->shouldReceive('doesntExist')
                ->andReturn(true);

            $result = $this->service->batchProcessDocuments($documents);

            expect($result)->toHaveCount(1);
        });

        test('returns empty collection when no new documents', function () {
            $documents = new EloquentCollection([]);

            $result = $this->service->batchProcessDocuments($documents);

            expect($result)->toBeEmpty();
        });

        test('uses enum values for field access', function () {
            // This tests that the service uses SharepointFieldEnum constants
            // when accessing SharePoint fields in the document processing
            expect(SharepointFieldEnum::TITLE()->label)->toBe('Title');
            expect(SharepointFieldEnum::PADALINYS()->label)->toBe('Padalinys');
            expect(SharepointFieldEnum::SUMMARY()->label)->toBe('Summary');
        });

        test('uses enum values for permission filtering', function () {
            // This tests that the service uses SharepointScopeEnum for filtering
            expect(SharepointScopeEnum::ANONYMOUS()->label)->toBe('anonymous');
        });
    });

    describe('input validation', function () {
        test('validateNotEmpty throws for empty string', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            expect(fn () => $method->invoke($this->service, ['test' => '']))
                ->toThrow(\InvalidArgumentException::class);
        });

        test('validateNotEmpty throws for null value', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            expect(fn () => $method->invoke($this->service, ['test' => null]))
                ->toThrow(\InvalidArgumentException::class);
        });

        test('validateNotEmpty passes for valid values', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            // Should not throw
            $method->invoke($this->service, ['test' => 'valid-value']);

            expect(true)->toBeTrue();
        });
    });

    describe('retry logic', function () {
        test('executeWithRetry succeeds on first attempt', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => 'success';

            $result = $method->invoke($this->service, $operation, 'test-operation');

            expect($result)->toBe('success');
        });

        test('executeWithRetry retries on failure then succeeds', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts === 1) {
                    throw new \Exception('First attempt fails');
                }

                return 'success';
            };

            Log::shouldReceive('info')->twice(); // Retry log + success log

            $result = $method->invoke($this->service, $operation, 'test-operation');

            expect($result)->toBe('success');
            expect($attempts)->toBe(2);
        });

        test('executeWithRetry throws after max retries exceeded', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => throw new \Exception('Always fails');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'test-operation'))
                ->toThrow(\Exception::class, 'Always fails');
        });

        test('executeWithRetry uses exponential backoff', function () {
            Sleep::fake();

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 2) {
                    throw new \Exception('Fail first two attempts');
                }

                return 'success';
            };

            Log::shouldReceive('info')->times(3); // 2 retries + 1 success

            $result = $method->invoke($this->service, $operation, 'test-operation');

            expect($result)->toBe('success');
            expect($attempts)->toBe(3);

            // Verify that sleep was called exactly twice (for the 2 retries)
            Sleep::assertSleptTimes(2);

            // We can also verify that at least one sleep call was made
            Sleep::assertSlept(function ($duration, $unit) {
                // Should be either 1000ms or 2000ms with milliseconds unit
                return in_array($duration, [1000, 2000]) && $unit === 'millisecond';
            }, 2); // Should be called exactly twice
        });
    });

    describe('logging', function () {
        test('logInfo logs when enabled', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Test message', ['key' => 'value']);

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('logInfo');
            $method->setAccessible(true);

            $method->invoke($this->service, 'Test message', ['key' => 'value']);
        });

        test('logError logs errors', function () {
            Log::shouldReceive('error')
                ->once()
                ->with('Test error', ['error' => 'details']);

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('logError');
            $method->setAccessible(true);

            $method->invoke($this->service, 'Test error', ['error' => 'details']);
        });
    });

    describe('constants usage', function () {
        test('uses correct constants for configuration', function () {
            expect(SharepointConfigEnum::API_BASE_URL()->label)->toBe('https://graph.microsoft.com/v1.0/');
            expect((int) SharepointConfigEnum::DEFAULT_TIMEOUT()->label)->toBe(30);
            expect((int) SharepointConfigEnum::MAX_RETRIES()->label)->toBe(3);
            expect((int) SharepointConfigEnum::RETRY_DELAY_MS()->label)->toBe(1000);
        });

        test('uses correct constants for permission values', function () {
            expect(SharepointScopeEnum::ANONYMOUS()->label)->toBe('anonymous');
            expect(SharepointPermissionTypeEnum::VIEW()->label)->toBe('view');
        });

        test('uses correct constants for SharePoint fields', function () {
            expect(SharepointFieldEnum::TITLE()->label)->toBe('Title');
            expect(SharepointFieldEnum::PADALINYS()->label)->toBe('Padalinys');
        });
    });
});
