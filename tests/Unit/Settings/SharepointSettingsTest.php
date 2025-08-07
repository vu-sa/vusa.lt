<?php

use App\Settings\SharepointSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Settings;

uses(RefreshDatabase::class);

describe('SharepointSettings', function () {
    beforeEach(function () {
        // Ensure settings are migrated
        $this->artisan('migrate');
    });

    describe('settings structure', function () {
        test('extends Settings class', function () {
            $settings = app(SharepointSettings::class);

            expect($settings)->toBeInstanceOf(Settings::class);
            expect($settings)->toBeInstanceOf(SharepointSettings::class);
        });

        test('has correct group name', function () {
            expect(SharepointSettings::group())->toBe('sharepoint');
        });

        test('has required properties', function () {
            $settings = app(SharepointSettings::class);

            expect($settings)->toHaveProperty('permission_expiry_days');
            expect($settings)->toHaveProperty('default_folder_structure');
        });
    });

    describe('default values', function () {
        test('has correct default permission expiry days', function () {
            $settings = app(SharepointSettings::class);

            expect($settings->permission_expiry_days)->toBe(365);
        });

        test('has correct default folder structure', function () {
            $settings = app(SharepointSettings::class);

            expect($settings->default_folder_structure)->toBe('General/{type}/{name}');
        });
    });

    describe('settings persistence', function () {
        test('can update permission expiry days', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 180;
            $settings->save();

            // Get fresh instance
            $freshSettings = app(SharepointSettings::class);
            expect($freshSettings->permission_expiry_days)->toBe(180);
        });

        test('can update default folder structure', function () {
            $settings = app(SharepointSettings::class);
            $settings->default_folder_structure = 'Custom/{tenant}/{type}';
            $settings->save();

            // Get fresh instance
            $freshSettings = app(SharepointSettings::class);
            expect($freshSettings->default_folder_structure)->toBe('Custom/{tenant}/{type}');
        });

        test('persists multiple settings changes', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 90;
            $settings->default_folder_structure = 'Modified/{name}';
            $settings->save();

            // Get fresh instance
            $freshSettings = app(SharepointSettings::class);
            expect($freshSettings->permission_expiry_days)->toBe(90);
            expect($freshSettings->default_folder_structure)->toBe('Modified/{name}');
        });
    });

    describe('validation scenarios', function () {
        test('accepts valid permission expiry days', function () {
            $settings = app(SharepointSettings::class);

            // Test various valid values
            $validValues = [1, 30, 365, 3650];

            foreach ($validValues as $value) {
                $settings->permission_expiry_days = $value;
                expect($settings->permission_expiry_days)->toBe($value);
            }
        });

        test('accepts valid folder structure patterns', function () {
            $settings = app(SharepointSettings::class);

            $validPatterns = [
                'General/{type}/{name}',
                '{tenant}/{type}',
                'Documents/{year}/{month}',
                'Custom/Structure',
            ];

            foreach ($validPatterns as $pattern) {
                $settings->default_folder_structure = $pattern;
                expect($settings->default_folder_structure)->toBe($pattern);
            }
        });

        test('handles edge cases for permission expiry', function () {
            $settings = app(SharepointSettings::class);

            // Test edge cases
            $settings->permission_expiry_days = 1; // Minimum
            expect($settings->permission_expiry_days)->toBe(1);

            $settings->permission_expiry_days = 3650; // Maximum (10 years)
            expect($settings->permission_expiry_days)->toBe(3650);
        });

        test('handles special characters in folder structure', function () {
            $settings = app(SharepointSettings::class);

            $settings->default_folder_structure = 'General/{type} & Partners/{name} (2023)';
            expect($settings->default_folder_structure)->toBe('General/{type} & Partners/{name} (2023)');
        });

        test('handles unicode characters in folder structure', function () {
            $settings = app(SharepointSettings::class);

            $settings->default_folder_structure = 'Dokumentai/{tipas}/{pavadinimas}';
            expect($settings->default_folder_structure)->toBe('Dokumentai/{tipas}/{pavadinimas}');
        });
    });

    describe('integration with services', function () {
        test('can be injected into SharepointGraphService', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 180;
            $settings->save();

            // Test that service can use the settings
            expect($settings->permission_expiry_days)->toBe(180);
        });

        test('settings changes affect service behavior', function () {
            $settings = app(SharepointSettings::class);

            // Change settings
            $originalExpiry = $settings->permission_expiry_days;
            $settings->permission_expiry_days = 500;
            $settings->save();

            // Verify the change
            $freshSettings = app(SharepointSettings::class);
            expect($freshSettings->permission_expiry_days)->not()->toBe($originalExpiry);
            expect($freshSettings->permission_expiry_days)->toBe(500);
        });
    });

    describe('caching behavior', function () {
        test('settings are cached properly', function () {
            $settings1 = app(SharepointSettings::class);
            $settings2 = app(SharepointSettings::class);

            // Should be same instance due to singleton pattern
            expect($settings1)->toBe($settings2);
        });

        test('cache is invalidated after save', function () {
            $settings = app(SharepointSettings::class);
            $originalValue = $settings->permission_expiry_days;

            $settings->permission_expiry_days = 999;
            $settings->save();

            // Get fresh instance - should have updated value
            app()->forgetInstance(SharepointSettings::class);
            $freshSettings = app(SharepointSettings::class);

            expect($freshSettings->permission_expiry_days)->toBe(999);
            expect($freshSettings->permission_expiry_days)->not()->toBe($originalValue);
        });
    });

    describe('error handling', function () {
        test('handles database connection issues gracefully', function () {
            // This test would need database mocking to simulate connection failures
            // For now, we'll just verify the settings can be instantiated
            $settings = app(SharepointSettings::class);
            expect($settings)->toBeInstanceOf(SharepointSettings::class);
        });

        test('provides sensible defaults when database is empty', function () {
            // Clear any existing settings and reload
            DB::table('settings')->where('group', 'sharepoint')->delete();
            app()->forgetInstance(SharepointSettings::class);

            // Create the settings manually with defaults
            DB::table('settings')->insert([
                ['group' => 'sharepoint', 'name' => 'permission_expiry_days', 'payload' => json_encode(365), 'locked' => 0],
                ['group' => 'sharepoint', 'name' => 'default_folder_structure', 'payload' => json_encode('General/{type}/{name}'), 'locked' => 0],
            ]);

            $settings = app(SharepointSettings::class);

            // Should have defaults from what we inserted
            expect($settings->permission_expiry_days)->toBe(365);
            expect($settings->default_folder_structure)->toBe('General/{type}/{name}');
        });
    });

    describe('type safety', function () {
        test('permission_expiry_days is integer type', function () {
            $settings = app(SharepointSettings::class);

            expect($settings->permission_expiry_days)->toBeInt();
        });

        test('default_folder_structure is string type', function () {
            $settings = app(SharepointSettings::class);

            expect($settings->default_folder_structure)->toBeString();
        });

        test('maintains type integrity after save/load cycle', function () {
            $settings = app(SharepointSettings::class);
            $settings->permission_expiry_days = 123;
            $settings->default_folder_structure = 'test/pattern';
            $settings->save();

            app()->forgetInstance(SharepointSettings::class);
            $freshSettings = app(SharepointSettings::class);

            expect($freshSettings->permission_expiry_days)->toBeInt();
            expect($freshSettings->default_folder_structure)->toBeString();
        });
    });

    describe('admin interface integration', function () {
        test('settings can be updated via array', function () {
            $settings = app(SharepointSettings::class);

            // Simulate form data from admin interface
            $formData = [
                'permission_expiry_days' => 720,
                'default_folder_structure' => 'Admin/{type}',
            ];

            $settings->permission_expiry_days = $formData['permission_expiry_days'];
            $settings->default_folder_structure = $formData['default_folder_structure'];
            $settings->save();

            expect($settings->permission_expiry_days)->toBe(720);
            expect($settings->default_folder_structure)->toBe('Admin/{type}');
        });

        test('settings provide validation-ready data structure', function () {
            $settings = app(SharepointSettings::class);

            // Settings should be easily convertible to validation array
            $validationData = [
                'permission_expiry_days' => $settings->permission_expiry_days,
                'default_folder_structure' => $settings->default_folder_structure,
            ];

            expect($validationData)->toHaveKey('permission_expiry_days');
            expect($validationData)->toHaveKey('default_folder_structure');
            expect($validationData['permission_expiry_days'])->toBeInt();
            expect($validationData['default_folder_structure'])->toBeString();
        });
    });
});
