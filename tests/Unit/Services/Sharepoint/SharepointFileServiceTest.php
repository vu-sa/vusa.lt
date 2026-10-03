<?php

use App\Enums\SharepointFolderEnum;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

describe('SharepointFileService', function (): void {
    beforeEach(function (): void {
        $this->service = new SharepointFileService;
        $this->tenant = Tenant::factory()->create(['shortname' => 'test-tenant']);
    });

    describe('folderUrlOrNull', function (): void {
        test('appends the encoded folder path to the configured drive address', function (): void {
            config(['filesystems.sharepoint.vusa_drive_url' => 'https://example.sharepoint.com/sites/vusa/Shared Documents/']);
            $institution = Institution::factory()->for($this->tenant)->create(['name' => ['lt' => 'Studijų komitetas', 'en' => 'Study committee']]);

            expect(SharepointFileService::folderUrlOrNull($institution))
                ->toBe('https://example.sharepoint.com/sites/vusa/Shared Documents/General/Padaliniai/test-tenant/Institutions/Studij%C5%B3%20komitetas');
        });

        test('is null when no drive address is configured', function (): void {
            config(['filesystems.sharepoint.vusa_drive_url' => null]);
            $institution = Institution::factory()->for($this->tenant)->create();

            expect(SharepointFileService::folderUrlOrNull($institution))->toBeNull();
        });
    });

    describe('pathForFileableDriveItem (human-readable paths)', function (): void {
        test('throws exception for models without HasSharepointFiles trait', function (): void {
            $modelWithoutTrait = new class extends Model {};

            expect(fn () => SharepointFileService::pathForFileableDriveItem($modelWithoutTrait))
                ->toThrow(Exception::class, 'Model does not have HasSharepointFiles trait');
        });

        test('generates correct path for InstitutionType model', function (): void {
            $type = InstitutionType::factory()->create([
                'title' => 'Test InstitutionType',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($type);

            expect($path)->toBe('General/Types/Institutions/Test InstitutionType');
        });

        test('generates correct path for Institution model', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toBe('General/Padaliniai/test-tenant/Institutions/Test Institution');
        });

        test('throws exception for Institution without tenant', function (): void {
            $institution = Institution::factory()->make(['name' => 'Test', 'tenant_id' => null]);

            expect(fn () => SharepointFileService::pathForFileableDriveItem($institution))
                ->toThrow(Exception::class, 'Institution does not have a tenant. Tenant must be assigned.');
        });

        test('generates correct path for Meeting model', function (): void {
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

        test('generates correct path for Meeting with empty title', function (): void {
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

        test('throws exception for Meeting without institution', function (): void {
            $meeting = Meeting::factory()->make(['title' => 'Test Meeting']);
            $meeting->setRelation('institutions', collect([]));

            expect(fn () => SharepointFileService::pathForFileableDriveItem($meeting))
                ->toThrow(Exception::class, 'Meeting does not have an institution. Institution must be assigned.');
        });

        test('throws exception for Meeting institution without tenant', function (): void {
            $institutionWithoutTenant = Institution::factory()->create(['name' => 'Test', 'tenant_id' => null]);
            $meeting = Meeting::factory()->create(['title' => 'Test Meeting']);

            $meeting->institutions()->attach($institutionWithoutTenant->id);

            expect(fn () => SharepointFileService::pathForFileableDriveItem($meeting))
                ->toThrow(Exception::class, 'Institution does not have a tenant. Tenant must be assigned.');
        });

        test('generates correct path for Duty model', function (): void {
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

        test('uses SharepointFolderEnum constants', function (): void {
            $type = InstitutionType::factory()->create([
                'title' => 'Test InstitutionType',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($type);

            expect($path)->toStartWith(SharepointFolderEnum::GENERAL->label());
        });

        test('handles different model types correctly', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain(SharepointFolderEnum::GENERAL->label())
                ->toContain(SharepointFolderEnum::PADALINIAI->label())
                ->toContain($this->tenant->shortname);
        });

        test('formats meeting datetime correctly', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create();

            $meeting = Meeting::factory()->create([
                'title' => 'Test Meeting',
                'start_time' => Carbon::create(2023, 12, 25, 9, 15, 30), // Christmas morning
            ]);

            $meeting->institutions()->attach($institution->id);

            $path = SharepointFileService::pathForFileableDriveItem($meeting);

            expect($path)->toContain('2023-12-25 09.15');
        });

        test('handles special characters in names', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Institution & Partners (Ltd.)',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institution & Partners (Ltd.)');
        });

        test('handles unicode characters in names', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Institucija ąčęėįšųūž',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institucija ąčęėįšųūž');
        });
    });

    describe('uploadFile', function (): void {
        test('validates fileable has HasSharepointFiles trait', function (): void {
            $modelWithoutTrait = new class extends Model {};
            $file = UploadedFile::fake()->create('test.pdf');

            expect(fn () => $this->service->uploadFile(
                $file,
                'test-filename.pdf',
                $modelWithoutTrait,
                []
            ))->toThrow(Exception::class, 'Model does not have HasSharepointFiles trait');
        });

        // Note: Full uploadFile testing would require mocking SharepointGraphService
        // which involves complex Graph API chains. This would be better tested
        // in integration tests with proper mocking setup.
    });

    describe('path generation edge cases', function (): void {
        test('handles empty institution name gracefully', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => '',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institutions/');
        });

        test('path includes proper folder structure', function (): void {
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

    describe('enum integration', function (): void {
        test('uses correct folder enum values', function (): void {
            expect(SharepointFolderEnum::GENERAL->label())->toBe('General')
                ->and(SharepointFolderEnum::PADALINIAI->label())->toBe('Padaliniai');
        });

        test('folder enums are used in paths', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create(['name' => 'Test Institution']);
            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain(SharepointFolderEnum::GENERAL->label())
                ->toContain(SharepointFolderEnum::PADALINIAI->label())
                ->toContain($this->tenant->shortname);
        });
    });
});
