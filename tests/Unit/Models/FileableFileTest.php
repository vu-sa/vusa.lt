<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Tenant;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

describe('FileableFile model basic functionality', function (): void {
    test('can create a FileableFile with required attributes', function (): void {
        $fileableFile = FileableFile::factory()->for($this->institution, 'fileable')->create();

        expect($fileableFile)->toBeInstanceOf(FileableFile::class)
            ->and($fileableFile->fileable_type)->toBe(MorphMap::alias(Institution::class))
            ->and($fileableFile->fileable_id)->toBe($this->institution->id)
            ->and($fileableFile->sharepoint_id)->toBeString()
            ->and($fileableFile->name)->toBeString();
    });

    test('FileableFile uses ULID as primary key', function (): void {
        $fileableFile = FileableFile::factory()->for($this->institution, 'fileable')->create();

        // ULIDs are 26 characters long
        expect($fileableFile->id)->toHaveLength(26);
    });

    test('FileableFile belongs to a fileable model', function (): void {
        $fileableFile = FileableFile::factory()->for($this->institution, 'fileable')->create();

        expect($fileableFile->fileable)->toBeInstanceOf(Institution::class)
            ->and($fileableFile->fileable->id)->toBe($this->institution->id);
    });

    test('institution can have multiple FileableFiles', function (): void {
        FileableFile::factory()->count(3)->for($this->institution, 'fileable')->create();

        $this->institution->refresh();

        expect($this->institution->fileableFiles)->toHaveCount(3);
    });
});

describe('FileableFile scopes', function (): void {
    test('ofType scope filters by file type', function (): void {
        FileableFile::factory()->for($this->institution, 'fileable')->protocol()->create();
        FileableFile::factory()->for($this->institution, 'fileable')->report()->create();
        FileableFile::factory()->for($this->institution, 'fileable')->create(['file_type' => FileableFile::TYPE_OTHER]);

        $protocolFiles = FileableFile::ofType(FileableFile::TYPE_PROTOCOL)->get();
        $reportFiles = FileableFile::ofType(FileableFile::TYPE_REPORT)->get();

        expect($protocolFiles)->toHaveCount(1)
            ->and($reportFiles)->toHaveCount(1);
    });

    test('notDeletedExternally scope excludes externally deleted files', function (): void {
        FileableFile::factory()->for($this->institution, 'fileable')->create();
        FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        $availableFiles = FileableFile::notDeletedExternally()->get();

        expect($availableFiles)->toHaveCount(1)
            ->and($availableFiles->first()->deleted_externally_at)->toBeNull();
    });

    test('available scope excludes externally deleted files', function (): void {
        $normalFile = FileableFile::factory()->for($this->institution, 'fileable')->create();
        FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        $availableFiles = FileableFile::available()->get();

        expect($availableFiles)->toHaveCount(1)
            ->and($availableFiles->first()->id)->toBe($normalFile->id);
    });
});

describe('FileableFile trait methods on fileable models', function (): void {
    test('hasFileOfType returns true when file exists', function (): void {
        FileableFile::factory()->for($this->institution, 'fileable')->protocol()->create();

        expect($this->institution->hasFileOfType(FileableFile::TYPE_PROTOCOL))->toBeTrue()
            ->and($this->institution->hasFileOfType(FileableFile::TYPE_REPORT))->toBeFalse();
    });

    test('hasFileOfType excludes externally deleted files', function (): void {
        FileableFile::factory()->for($this->institution, 'fileable')->protocol()->deletedExternally()->create();

        expect($this->institution->hasFileOfType(FileableFile::TYPE_PROTOCOL))->toBeFalse();
    });

    test('availableFiles relationship excludes deleted files', function (): void {
        FileableFile::factory()->for($this->institution, 'fileable')->create();
        FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        expect($this->institution->availableFiles)->toHaveCount(1);
    });
});

describe('FileableFile attributes and accessors', function (): void {
    test('formattedSize returns human-readable file size', function (): void {
        $smallFile = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => 500]);
        $mediumFile = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => 1536]); // 1.5 KB
        $largeFile = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => 1572864]); // 1.5 MB

        expect($smallFile->formatted_size)->toBe('500 B');
        expect($mediumFile->formatted_size)->toBe('1.5 KB')
            ->and($largeFile->formatted_size)->toBe('1.5 MB');
    });

    test('formattedSize returns null when size_bytes is null', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => null]);

        expect($file->formatted_size)->toBeNull();
    });

    test('fileTypeLabel returns localized label', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->protocol()->create();

        // FileTypes are stored in Lithuanian (matching SharePoint metadata labels)
        expect($file->file_type_label)->toBe(FileableFile::TYPE_PROTOCOL);
    });

    test('fileTypeLabel returns null when file_type is null', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create(['file_type' => null]);

        expect($file->file_type_label)->toBeNull();
    });
});

describe('FileableFile external deletion tracking', function (): void {
    test('markAsDeletedExternally sets the timestamp', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create();

        expect($file->deleted_externally_at)->toBeNull();

        $file->markAsDeletedExternally();

        expect($file->deleted_externally_at)->not()->toBeNull();
    });

    test('isDeletedExternally returns correct boolean', function (): void {
        $normalFile = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $deletedFile = FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        expect($normalFile->isDeletedExternally())->toBeFalse()
            ->and($deletedFile->isDeletedExternally())->toBeTrue();
    });
});

describe('FileableFile public link functionality', function (): void {
    test('hasExpiredPublicLink returns false when no expiry set', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create([
            'public_link_expires_at' => null,
        ]);

        expect($file->hasExpiredPublicLink())->toBeFalse();
    });

    test('hasExpiredPublicLink returns true when link is expired', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->withExpiredPublicLink()->create();

        expect($file->hasExpiredPublicLink())->toBeTrue();
    });

    test('hasExpiredPublicLink returns false when link is still valid', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->withPublicLink()->create();

        expect($file->hasExpiredPublicLink())->toBeFalse();
    });
});

describe('FileableFile static helpers', function (): void {
    test('fileTypes returns all available types with labels', function (): void {
        $types = FileableFile::fileTypes();

        expect($types)->toHaveKey(FileableFile::TYPE_PROTOCOL)
            ->toHaveKey(FileableFile::TYPE_REPORT)
            ->toHaveKey(FileableFile::TYPE_AGENDA)
            ->toHaveKey(FileableFile::TYPE_METHODOLOGY)
            ->toHaveKey(FileableFile::TYPE_OTHER);
    });
});

describe('FileableFile deletion', function (): void {
    test('deleted files are permanently removed', function (): void {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $file->delete();

        expect(FileableFile::find($file->id))->toBeNull();
    });
});
