<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

describe('FileableFile model basic functionality', function () {
    test('can create a FileableFile with required attributes', function () {
        $fileableFile = FileableFile::factory()->for($this->institution, 'fileable')->create();

        expect($fileableFile)->toBeInstanceOf(FileableFile::class);
        expect($fileableFile->fileable_type)->toBe(Institution::class);
        expect($fileableFile->fileable_id)->toBe($this->institution->id);
        expect($fileableFile->sharepoint_id)->toBeString();
        expect($fileableFile->name)->toBeString();
    });

    test('FileableFile uses ULID as primary key', function () {
        $fileableFile = FileableFile::factory()->for($this->institution, 'fileable')->create();

        // ULIDs are 26 characters long
        expect(strlen($fileableFile->id))->toBe(26);
    });

    test('FileableFile belongs to a fileable model', function () {
        $fileableFile = FileableFile::factory()->for($this->institution, 'fileable')->create();

        expect($fileableFile->fileable)->toBeInstanceOf(Institution::class);
        expect($fileableFile->fileable->id)->toBe($this->institution->id);
    });

    test('institution can have multiple FileableFiles', function () {
        FileableFile::factory()->count(3)->for($this->institution, 'fileable')->create();

        $this->institution->refresh();

        expect($this->institution->fileableFiles)->toHaveCount(3);
    });
});

describe('FileableFile scopes', function () {
    test('ofType scope filters by file type', function () {
        FileableFile::factory()->for($this->institution, 'fileable')->protocol()->create();
        FileableFile::factory()->for($this->institution, 'fileable')->report()->create();
        FileableFile::factory()->for($this->institution, 'fileable')->create(['file_type' => FileableFile::TYPE_OTHER]);

        $protocolFiles = FileableFile::ofType(FileableFile::TYPE_PROTOCOL)->get();
        $reportFiles = FileableFile::ofType(FileableFile::TYPE_REPORT)->get();

        expect($protocolFiles)->toHaveCount(1);
        expect($reportFiles)->toHaveCount(1);
    });

    test('notDeletedExternally scope excludes externally deleted files', function () {
        FileableFile::factory()->for($this->institution, 'fileable')->create();
        FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        $availableFiles = FileableFile::notDeletedExternally()->get();

        expect($availableFiles)->toHaveCount(1);
        expect($availableFiles->first()->deleted_externally_at)->toBeNull();
    });

    test('available scope excludes both soft-deleted and externally deleted files', function () {
        $normalFile = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $externallyDeleted = FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();
        $softDeleted = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $softDeleted->delete();

        $availableFiles = FileableFile::available()->get();

        expect($availableFiles)->toHaveCount(1);
        expect($availableFiles->first()->id)->toBe($normalFile->id);
    });
});

describe('FileableFile trait methods on fileable models', function () {
    test('hasFileOfType returns true when file exists', function () {
        FileableFile::factory()->for($this->institution, 'fileable')->protocol()->create();

        expect($this->institution->hasFileOfType(FileableFile::TYPE_PROTOCOL))->toBeTrue();
        expect($this->institution->hasFileOfType(FileableFile::TYPE_REPORT))->toBeFalse();
    });

    test('hasFileOfType excludes externally deleted files', function () {
        FileableFile::factory()->for($this->institution, 'fileable')->protocol()->deletedExternally()->create();

        expect($this->institution->hasFileOfType(FileableFile::TYPE_PROTOCOL))->toBeFalse();
    });

    test('availableFiles relationship excludes deleted files', function () {
        FileableFile::factory()->for($this->institution, 'fileable')->create();
        FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        expect($this->institution->availableFiles)->toHaveCount(1);
    });
});

describe('FileableFile attributes and accessors', function () {
    test('formattedSize returns human-readable file size', function () {
        $smallFile = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => 500]);
        $mediumFile = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => 1536]); // 1.5 KB
        $largeFile = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => 1572864]); // 1.5 MB

        expect($smallFile->formatted_size)->toBe('500 B');
        expect($mediumFile->formatted_size)->toBe('1.5 KB');
        expect($largeFile->formatted_size)->toBe('1.5 MB');
    });

    test('formattedSize returns null when size_bytes is null', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create(['size_bytes' => null]);

        expect($file->formatted_size)->toBeNull();
    });

    test('fileTypeLabel returns localized label', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->protocol()->create();

        expect($file->file_type_label)->toBe('Protocol');
    });

    test('fileTypeLabel returns null when file_type is null', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create(['file_type' => null]);

        expect($file->file_type_label)->toBeNull();
    });
});

describe('FileableFile external deletion tracking', function () {
    test('markAsDeletedExternally sets the timestamp', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create();

        expect($file->deleted_externally_at)->toBeNull();

        $file->markAsDeletedExternally();

        expect($file->deleted_externally_at)->not()->toBeNull();
    });

    test('isDeletedExternally returns correct boolean', function () {
        $normalFile = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $deletedFile = FileableFile::factory()->for($this->institution, 'fileable')->deletedExternally()->create();

        expect($normalFile->isDeletedExternally())->toBeFalse();
        expect($deletedFile->isDeletedExternally())->toBeTrue();
    });
});

describe('FileableFile public link functionality', function () {
    test('hasExpiredPublicLink returns false when no expiry set', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create([
            'public_link_expires_at' => null,
        ]);

        expect($file->hasExpiredPublicLink())->toBeFalse();
    });

    test('hasExpiredPublicLink returns true when link is expired', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->withExpiredPublicLink()->create();

        expect($file->hasExpiredPublicLink())->toBeTrue();
    });

    test('hasExpiredPublicLink returns false when link is still valid', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->withPublicLink()->create();

        expect($file->hasExpiredPublicLink())->toBeFalse();
    });
});

describe('FileableFile static helpers', function () {
    test('fileTypes returns all available types with labels', function () {
        $types = FileableFile::fileTypes();

        expect($types)->toHaveKey(FileableFile::TYPE_PROTOCOL);
        expect($types)->toHaveKey(FileableFile::TYPE_REPORT);
        expect($types)->toHaveKey(FileableFile::TYPE_AGENDA);
        expect($types)->toHaveKey(FileableFile::TYPE_METHODOLOGY);
        expect($types)->toHaveKey(FileableFile::TYPE_OTHER);
    });
});

describe('FileableFile soft deletion', function () {
    test('soft deleted files are excluded from normal queries', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $file->delete();

        expect(FileableFile::find($file->id))->toBeNull();
        expect(FileableFile::withTrashed()->find($file->id))->not()->toBeNull();
    });

    test('soft deleted files can be restored', function () {
        $file = FileableFile::factory()->for($this->institution, 'fileable')->create();
        $file->delete();
        $file->restore();

        expect(FileableFile::find($file->id))->not()->toBeNull();
    });
});
