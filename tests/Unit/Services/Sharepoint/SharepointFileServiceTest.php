<?php

use App\Enums\SharepointFolderEnum;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\Sharepoint\SharepointFileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

pest()->use(RefreshDatabase::class);

describe('SharepointFileService', function (): void {
    beforeEach(function (): void {
        $this->service = new SharepointFileService;
        $this->tenant = Tenant::query()->where('alias', 'mif')->firstOrFail();
    });

    describe('folderUrlOrNull', function (): void {
        test('appends the encoded folder path to the configured drive address', function (): void {
            config(['filesystems.sharepoint.vusa_drive_url' => 'https://example.sharepoint.com/sites/vusa/Shared Documents/']);
            $institution = Institution::factory()->for($this->tenant)->create(['name' => ['lt' => 'Studijų komitetas', 'en' => 'Study committee']]);
            $encodedTenant = rawurlencode($this->tenant->shortname);

            expect(SharepointFileService::folderUrlOrNull($institution))
                ->toBe("https://example.sharepoint.com/sites/vusa/Shared Documents/General/Padaliniai/{$encodedTenant}/Institutions/Studij%C5%B3%20komitetas");
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

        test('generates correct path for Institution model', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toBe("General/Padaliniai/{$this->tenant->shortname}/Institutions/Test Institution");
        });

        test('throws exception for Institution without tenant', function (): void {
            $institution = Institution::factory()->make(['name' => 'Test', 'tenant_id' => null]);

            expect(fn () => SharepointFileService::pathForFileableDriveItem($institution))
                ->toThrow(Exception::class, 'Institution does not have a tenant. Tenant must be assigned.');
        });

        test('generates correct path for Meeting model with zero-padded hour and truncated seconds', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => 'Test Institution',
            ]);

            $meeting = Meeting::factory()->create([
                'title' => 'Test Meeting',
                'start_time' => Carbon::create(2023, 6, 15, 9, 5, 30),
            ]);

            $meeting->institutions()->attach($institution->id);

            $path = SharepointFileService::pathForFileableDriveItem($meeting);

            expect($path)->toBe("General/Padaliniai/{$this->tenant->shortname}/Institutions/Test Institution/Meetings/2023-06-15 09.05");
        });

        test('keeps a joint meeting in its first institution folder when another is attached', function (): void {
            $joinedLater = Institution::factory()->for($this->tenant)->create(['name' => 'Ankstesnė institucija']);
            $original = Institution::factory()->for($this->tenant)->create(['name' => 'Pirminė institucija']);
            $meeting = Meeting::factory()->create(['start_time' => Carbon::create(2023, 6, 15, 14, 30)]);

            // Stored out of order: MariaDB returns pivot rows by primary key, not by when they were attached.
            $meeting->institutions()->attach($joinedLater->id, ['created_at' => now()]);
            $meeting->institutions()->attach($original->id, ['created_at' => now()->subDay()]);

            expect(SharepointFileService::pathForFileableDriveItem($meeting->fresh()))
                ->toBe("General/Padaliniai/{$this->tenant->shortname}/Institutions/Pirminė institucija/Meetings/2023-06-15 14.30");
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

            expect($path)->toBe("General/Padaliniai/{$this->tenant->shortname}/Institutions/Test Institution/Duties/Pareigybė");
        });

        test('names folders in Lithuanian whatever the request locale', function (): void {
            app()->setLocale('en');

            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => ['lt' => 'Studijų komitetas', 'en' => 'Study committee'],
            ]);
            $duty = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Narys', 'en' => 'Member']]);
            $meeting = Meeting::factory()->create(['start_time' => Carbon::create(2023, 6, 15, 14, 30)]);
            $meeting->institutions()->attach($institution->id);

            expect(SharepointFileService::pathForFileableDriveItem($meeting))->toBe("General/Padaliniai/{$this->tenant->shortname}/Institutions/Studijų komitetas/Meetings/2023-06-15 14.30")
                ->and(SharepointFileService::pathForFileableDriveItem($duty))->toBe("General/Padaliniai/{$this->tenant->shortname}/Institutions/Studijų komitetas/Duties/Narys");
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
    });

    describe('path generation edge cases', function (): void {
        test('handles empty institution name gracefully', function (): void {
            $institution = Institution::factory()->for($this->tenant)->create([
                'name' => '',
            ]);

            $path = SharepointFileService::pathForFileableDriveItem($institution);

            expect($path)->toContain('Institutions/');
        });
    });

    describe('enum integration', function (): void {
        test('uses correct folder enum values', function (): void {
            expect(SharepointFolderEnum::GENERAL->label())->toBe('General')
                ->and(SharepointFolderEnum::PADALINIAI->label())->toBe('Padaliniai');
        });
    });
});
