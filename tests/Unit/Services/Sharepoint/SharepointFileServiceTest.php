<?php

use App\Enums\SharepointFolderEnum;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\Type;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('SharepointFileService', function () {
    beforeEach(function () {
        $this->service = new SharepointFileService;
        $this->tenant = Tenant::factory()->create(['shortname' => 'test-tenant']);
    });

    describe('generateUniqueFolderName', function () {
        test('generates folder name with last 4 characters of ID', function () {
            $fileableId = '12345678901234567890abcd';
            $fileableName = 'Test Document';

            $result = $this->service->generateUniqueFolderName($fileableId, $fileableName);

            expect($result)->toBe('Test Document-abcd');
        });

        test('handles short IDs gracefully', function () {
            $fileableId = 'abc';
            $fileableName = 'Test';

            $result = $this->service->generateUniqueFolderName($fileableId, $fileableName);

            expect($result)->toBe('Test-abc');
        });

        test('handles empty name', function () {
            $fileableId = '1234567890abcdef';
            $fileableName = '';

            $result = $this->service->generateUniqueFolderName($fileableId, $fileableName);

            expect($result)->toBe('-cdef');
        });
    });

    describe('pathForFileableDriveItem (human-readable paths)', function () {
        test('throws exception for models without HasSharepointFiles trait', function () {
            $modelWithoutTrait = new class extends \Illuminate\Database\Eloquent\Model {};

            expect(fn () => SharepointFileService::pathForFileableDriveItem($modelWithoutTrait))
                ->toThrow(\Exception::class, 'Model does not have HasSharepointFiles trait');
        });

        test('generates correct path for Type model', function () {
            $type = Type::factory()->create([
                'title' => 'Test Type',
                'model_type' => 'App\\Models\\News',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($type);

            expect($path)->toBe('General/Types/News/Test Type');
        });

        test('generates correct path for Institution model', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toBe('General/Padaliniai/test-tenant/Institutions/Test Institution');
        });

        test('throws exception for Institution without tenant', function () {
            $institution = Institution::factory()->make(['name' => 'Test', 'tenant_id' => null]);

            expect(fn () => SharepointFileService::pathForFileableDriveItem($institution))
                ->toThrow(\Exception::class, 'Institution does not have a tenant. Tenant must be assigned.');
        });

        test('generates correct path for Meeting model', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $meeting = Meeting::factory()->create([
                'title' => 'Test Meeting',
                'start_time' => Carbon::create(2023, 6, 15, 14, 30),
            ]);

            $meeting->institutions()->attach($institution->id);

            $path = SharepointFileService::pathForFileableDriveItem($meeting);

            // Meeting path uses only datetime, not title
            expect($path)->toBe('General/Padaliniai/test-tenant/Institutions/Test Institution/Meetings/2023-06-15 14.30');
        });

        test('generates correct path for Meeting with empty title', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $meeting = Meeting::factory()->create([
                'title' => '',
                'start_time' => Carbon::create(2023, 6, 15, 14, 30),
            ]);

            $meeting->institutions()->attach($institution->id);

            $path = SharepointFileService::pathForFileableDriveItem($meeting);

            // Meeting path uses only datetime, not title
            expect($path)->toBe('General/Padaliniai/test-tenant/Institutions/Test Institution/Meetings/2023-06-15 14.30');
        });

        test('throws exception for Meeting without institution', function () {
            $meeting = Meeting::factory()->make(['title' => 'Test Meeting']);
            $meeting->setRelation('institutions', collect([]));

            expect(fn () => SharepointFileService::pathForFileableDriveItem($meeting))
                ->toThrow(\Exception::class, 'Meeting does not have an institution. Institution must be assigned.');
        });

        test('throws exception for Meeting institution without tenant', function () {
            $institutionWithoutTenant = Institution::factory()->create(['name' => 'Test', 'tenant_id' => null]);
            $meeting = Meeting::factory()->create(['title' => 'Test Meeting']);

            $meeting->institutions()->attach($institutionWithoutTenant->id);

            expect(fn () => SharepointFileService::pathForFileableDriveItem($meeting))
                ->toThrow(\Exception::class, 'Institution does not have a tenant. Tenant must be assigned.');
        });

        test('generates correct path for Duty model', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $duty = Duty::factory()->for($institution)->create([
                'name' => ['lt' => 'Pareigybė', 'en' => 'Duty'],
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($duty);

            expect($path)->toBe('General/Padaliniai/test-tenant/Institutions/Test Institution/Duties/Pareigybė');
        });

        // Note: Duty has a NOT NULL constraint on institution_id in the database,
        // so the exception path cannot be tested directly. The validation happens
        // at the service level when the institution relationship returns null.

        test('uses SharepointFolderEnum constants', function () {
            $type = Type::factory()->create([
                'title' => 'Test Type',
                'model_type' => 'App\\Models\\News',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($type);

            expect($path)->toStartWith(SharepointFolderEnum::GENERAL()->label);
        });

        test('handles different model types correctly', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain(SharepointFolderEnum::GENERAL()->label);
            expect($path)->toContain(SharepointFolderEnum::PADALINIAI()->label);
            expect($path)->toContain($this->tenant->shortname);
        });

        test('formats meeting datetime correctly', function () {
            $institution = Institution::factory()->for($this->tenant)->create();

            $meeting = Meeting::factory()->create([
                'title' => 'Test Meeting',
                'start_time' => Carbon::create(2023, 12, 25, 9, 15, 30), // Christmas morning
            ]);

            $meeting->institutions()->attach($institution->id);

            $path = SharepointFileService::pathForFileableDriveItem($meeting);

            expect($path)->toContain('2023-12-25 09.15');
        });

        test('handles special characters in names', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Institution & Partners (Ltd.)',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institution & Partners (Ltd.)');
        });

        test('handles unicode characters in names', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Institucija ąčęėįšųūž',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institucija ąčęėįšųūž');
        });
    });

    describe('uploadFile', function () {
        test('validates fileable has HasSharepointFiles trait', function () {
            $modelWithoutTrait = new class extends \Illuminate\Database\Eloquent\Model {};
            $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf');

            expect(fn () => $this->service->uploadFile(
                $file,
                'test-filename.pdf',
                $modelWithoutTrait,
                []
            ))->toThrow(\Exception::class, 'Model does not have HasSharepointFiles trait');
        });

        // Note: Full uploadFile testing would require mocking SharepointGraphService
        // which involves complex Graph API chains. This would be better tested
        // in integration tests with proper mocking setup.
    });

    describe('path generation edge cases', function () {
        test('handles empty institution name gracefully', function () {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => '',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institutions/');
        });

        test('path includes proper folder structure', function () {
            $institution1 = Institution::factory()->for($this->tenant)->create(['name' => 'Test 1']);
            $institution2 = Institution::factory()->for($this->tenant)->create(['name' => 'Test 2']);

            $path1 = SharepointFileService::pathForFileableDriveItem($institution1);
            $path2 = SharepointFileService::pathForFileableDriveItem($institution2);

            // Both should have same prefix structure
            expect($path1)->toStartWith('General/Padaliniai/test-tenant/Institutions/');
            expect($path2)->toStartWith('General/Padaliniai/test-tenant/Institutions/');
            // Each should have different ending based on name
            expect($path1)->toEndWith('Test 1');
            expect($path2)->toEndWith('Test 2');
        });
    });

    describe('enum integration', function () {
        test('uses correct folder enum values', function () {
            expect(SharepointFolderEnum::GENERAL()->label)->toBe('General');
            expect(SharepointFolderEnum::PADALINIAI()->label)->toBe('Padaliniai');
        });

        test('folder enums are used in paths', function () {
            $institution = Institution::factory()->for($this->tenant)->create(['name' => 'Test Institution']);
            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain(SharepointFolderEnum::GENERAL()->label);
            expect($path)->toContain(SharepointFolderEnum::PADALINIAI()->label);
            expect($path)->toContain($this->tenant->shortname);
        });
    });
});
