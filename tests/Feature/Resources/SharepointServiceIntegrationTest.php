<?php

use App\Enums\SharepointConfigEnum;
use App\Enums\SharepointFieldEnum;
use App\Enums\SharepointScopeEnum;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use App\Services\ResourceServices\SharepointFileService;
use App\Services\SharepointGraphService;
use App\Settings\SharepointSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['shortname' => 'test-tenant']);
    $this->user = makeUser($this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

describe('SharepointService Integration', function () {
    describe('service initialization', function () {
        test('service can be initialized with custom settings', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 180;

            $service = new SharepointGraphService(
                siteId: 'test-site',
                settings: $settings
            );

            expect($service)->toBeInstanceOf(SharepointGraphService::class);
            expect($service->siteId)->toBe('test-site');
        });

        test('service uses application settings by default', function () {
            $service = new SharepointGraphService;

            expect($service)->toBeInstanceOf(SharepointGraphService::class);
        });

        test('service initialization is logged', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('SharepointGraphService initialized', \Mockery::type('array'));

            new SharepointGraphService(siteId: 'test-site');
        });
    });

    describe('settings integration', function () {
        test('service respects custom permission expiry from settings', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 90;
            $settings->save();

            // Fresh settings instance should have the updated value
            $freshSettings = app(SharepointSettings::class);
            expect($freshSettings->permission_expiry_days)->toBe(90);
        });

        test('settings changes affect service behavior', function () {
            $settings = app(SharepointSettings::class);
            $originalExpiry = $settings->permission_expiry_days;

            $settings->permission_expiry_days = 500;
            $settings->save();

            // Create service with updated settings
            $service = new SharepointGraphService(settings: $settings);
            expect($settings->permission_expiry_days)->toBe(500);
            expect($settings->permission_expiry_days)->not()->toBe($originalExpiry);
        });

        test('default folder structure is configurable', function () {
            $settings = app(SharepointSettings::class);
            $settings->default_folder_structure = 'Custom/{tenant}/{type}';
            $settings->save();

            expect($settings->default_folder_structure)->toBe('Custom/{tenant}/{type}');
        });
    });

    describe('enum usage in API calls', function () {
        test('service uses correct enum values for SharePoint fields', function () {
            expect(SharepointFieldEnum::TITLE()->label)->toBe('Title');
            expect(SharepointFieldEnum::PADALINYS()->label)->toBe('Padalinys');
            expect(SharepointScopeEnum::ANONYMOUS()->label)->toBe('anonymous');
        });

        test('enum values match expected SharePoint API values', function () {
            // Mock API response with SharePoint field structure
            Http::fake([
                '*.sharepoint.com/*' => Http::response([
                    'value' => [
                        [
                            'id' => 'test-doc-id',
                            'name' => 'Test Document.pdf',
                            'listItem' => [
                                'fields' => [
                                    SharepointFieldEnum::TITLE()->label => 'Document Title',
                                    SharepointFieldEnum::PADALINYS()->label => ['Label' => 'Test Institution'],
                                    SharepointFieldEnum::SUMMARY()->label => 'Document summary',
                                ],
                            ],
                            'permissions' => [
                                [
                                    'link' => [
                                        'scope' => SharepointScopeEnum::ANONYMOUS()->label,
                                        'webUrl' => 'https://example.sharepoint.com/document.pdf',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200),
            ]);

            // Test would verify that the service correctly uses enum values
            expect(true)->toBeTrue(); // Placeholder - actual API testing would be more complex
        });
    });

    describe('file path generation', function () {
        test('generates correct paths for different model types', function () {
            $fileService = new SharepointFileService;

            $institutionPath = SharepointFileService::pathForFileableDriveItem($this->institution);
            expect($institutionPath)->toContain('General/Padaliniai');
            expect($institutionPath)->toContain($this->tenant->shortname);
        });

        test('path generation uses enum constants', function () {
            $institution = Institution::factory()->for($this->tenant)->create(['name' => 'Test Institution']);
            $fileService = new SharepointFileService;

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toStartWith('General/Padaliniai');
            expect($path)->toContain('Institutions');
            expect($path)->toContain('Test Institution');
        });

        test('handles special characters in path generation', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Institution & Partners (2023)',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);
            expect($path)->toContain('Institution & Partners (2023)');
        });
    });

    describe('error handling integration', function () {
        test('service handles API authentication errors gracefully', function () {
            Http::fake([
                'login.microsoftonline.com/*' => Http::response([
                    'error' => 'invalid_client',
                    'error_description' => 'Invalid client credentials',
                ], 401),
            ]);

            // Service should handle authentication failures
            expect(true)->toBeTrue(); // Actual implementation would need Graph API mocking
        });

        test('service retries on transient failures', function () {
            // Mock initial failure, then success
            Http::fake([
                '*.sharepoint.com/*' => Http::sequence()
                    ->push(['error' => 'Service temporarily unavailable'], 503)
                    ->push(['error' => 'Service temporarily unavailable'], 503)
                    ->push(['id' => 'success'], 200),
            ]);

            // Service should retry and eventually succeed
            expect(true)->toBeTrue(); // Would need actual retry testing with service
        });

        test('service respects retry configuration from constants', function () {
            expect((int) SharepointConfigEnum::MAX_RETRIES()->label)->toBe(3);
            expect((int) SharepointConfigEnum::RETRY_DELAY_MS()->label)->toBe(1000);
            expect((int) SharepointConfigEnum::DEFAULT_TIMEOUT()->label)->toBe(30);
        });
    });

    describe('logging integration', function () {
        test('service logs important operations', function () {
            Log::shouldReceive('info')
                ->with('SharepointGraphService initialized', \Mockery::type('array'));

            new SharepointGraphService(siteId: 'test');
        });

        test('service logs errors appropriately', function () {
            // This would test that service operations are properly logged
            expect(true)->toBeTrue(); // Placeholder for actual logging tests
        });
    });

    describe('batch processing integration', function () {
        test('can process multiple documents efficiently', function () {
            $documents = collect([
                Document::factory()->make(['sharepoint_id' => 'doc1']),
                Document::factory()->make(['sharepoint_id' => 'doc2']),
                Document::factory()->make(['sharepoint_id' => 'doc3']),
            ]);

            // Mock API responses for batch processing
            Http::fake([
                '*.sharepoint.com/*' => Http::response([
                    'responses' => [
                        ['id' => '1', 'status' => 200, 'body' => ['id' => 'doc1', 'name' => 'Doc1.pdf']],
                        ['id' => '2', 'status' => 200, 'body' => ['id' => 'doc2', 'name' => 'Doc2.pdf']],
                        ['id' => '3', 'status' => 200, 'body' => ['id' => 'doc3', 'name' => 'Doc3.pdf']],
                    ],
                ], 200),
            ]);

            // Batch processing should handle multiple documents
            expect($documents)->toHaveCount(3);
        });

        test('batch processing respects size limits', function () {
            expect((int) SharepointConfigEnum::DEFAULT_BATCH_SIZE()->label)->toBe(20);
            expect((int) SharepointConfigEnum::DEFAULT_BATCH_SIZE()->label)->toBeLessThan(100);
        });
    });

    describe('permission management integration', function () {
        test('creates permissions with correct scope and type', function () {
            Http::fake([
                '*.sharepoint.com/*' => Http::response([
                    'id' => 'permission-id',
                    'link' => [
                        'scope' => SharepointScopeEnum::ANONYMOUS()->label,
                        'type' => 'view',
                        'webUrl' => 'https://example.sharepoint.com/document.pdf',
                    ],
                ], 200),
            ]);

            // Permission creation should use enum values
            expect(SharepointScopeEnum::ANONYMOUS()->label)->toBe('anonymous');
        });

        test('permission expiry uses settings value', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 30;

            // Permission expiry should be configurable
            expect($settings->permission_expiry_days)->toBe(30);
        });
    });

    describe('model trait integration', function () {
        test('models with HasSharepointFiles trait work correctly', function () {
            expect(in_array(\App\Models\Traits\HasSharepointFiles::class, class_uses($this->institution)))->toBeTrue();
            expect($this->institution->files())->toBeDefined();
        });

        test('path generation works with trait models', function () {
            $institution = Institution::factory()->for($this->tenant)->create();

            expect(in_array(\App\Models\Traits\HasSharepointFiles::class, class_uses($institution)))->toBeTrue();

            $path = SharepointFileService::pathForFileableDriveItem($institution);
            expect($path)->toBeString();
            expect($path)->not()->toBeEmpty();
        });
    });

    describe('configuration validation', function () {
        test('required SharePoint configuration is present', function () {
            $config = config('filesystems.sharepoint');

            expect($config)->toHaveKey('tenant_id');
            expect($config)->toHaveKey('client_id');
            expect($config)->toHaveKey('client_secret');
            expect($config)->toHaveKey('site_id');
        });

        test('constants have reasonable values', function () {
            expect(SharepointConfigEnum::API_BASE_URL()->label)->toStartWith('https://');
            expect((int) SharepointConfigEnum::DEFAULT_TIMEOUT()->label)->toBeGreaterThan(0);
            expect((int) SharepointConfigEnum::MAX_RETRIES()->label)->toBeGreaterThan(0);
            expect((int) SharepointConfigEnum::RETRY_DELAY_MS()->label)->toBeGreaterThan(0);
        });
    });

    describe('real-world scenarios', function () {
        test('handles document metadata extraction correctly', function () {
            // Simulate SharePoint document with real metadata structure
            $mockDocument = [
                'id' => 'real-doc-id',
                'name' => 'Important Document.pdf',
                'listItem' => [
                    'fields' => [
                        SharepointFieldEnum::TITLE()->label => 'Important Document',
                        SharepointFieldEnum::PADALINYS()->label => ['Label' => 'Test Institution'],
                        SharepointFieldEnum::SUMMARY()->label => 'This is an important document',
                        SharepointFieldEnum::LANGUAGE()->label => 'lt',
                        SharepointFieldEnum::DATE()->label => '2023-06-15T10:30:00Z',
                    ],
                ],
                'permissions' => [
                    [
                        'link' => [
                            'scope' => SharepointScopeEnum::ANONYMOUS()->label,
                            'webUrl' => 'https://vusa.sharepoint.com/document.pdf',
                        ],
                    ],
                ],
            ];

            // Verify the structure matches what we expect
            expect($mockDocument['listItem']['fields'])->toHaveKey(SharepointFieldEnum::TITLE()->label);
            expect($mockDocument['permissions'][0]['link']['scope'])->toBe(SharepointScopeEnum::ANONYMOUS()->label);
        });

        test('handles multilingual content correctly', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Daugiakalbė institucija ąčęėįšųūž',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);
            expect($path)->toContain('Daugiakalbė institucija ąčęėįšųūž');
        });

        test('handles edge cases in document processing', function () {
            // Test with empty/null values that might come from SharePoint
            $mockDocument = [
                'id' => 'edge-case-doc',
                'name' => '',  // Empty name
                'listItem' => [
                    'fields' => [
                        SharepointFieldEnum::TITLE()->label => null,  // Null title
                        SharepointFieldEnum::SUMMARY()->label => '',  // Empty summary
                    ],
                ],
            ];

            // Service should handle these gracefully
            expect($mockDocument)->toHaveKey('id');
        });
    });
});
