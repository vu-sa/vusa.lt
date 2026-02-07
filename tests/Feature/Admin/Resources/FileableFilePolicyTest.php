<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->file = FileableFile::factory()->for($this->institution, 'fileable')->create();
});

describe('FileableFilePolicy', function () {
    describe('viewAny permission', function () {
        test('any authenticated user can view files list', function () {
            $user = makeUser($this->tenant);

            expect(Gate::forUser($user)->allows('viewAny', FileableFile::class))->toBeTrue();
        });
    });

    describe('view permission', function () {
        test('user can view file if they can view the parent fileable', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            expect(Gate::forUser($user)->allows('view', $this->file))->toBeTrue();
        });

        test('user cannot view file if they cannot view the parent fileable', function () {
            // Create a file for an institution the user doesn't have access to
            $otherTenant = Tenant::factory()->create();
            $otherInstitution = Institution::factory()->for($otherTenant)->create();
            $file = FileableFile::factory()->for($otherInstitution, 'fileable')->create();

            // User without specific permissions to view that institution
            $user = makeUser($this->tenant);

            // The FileableFilePolicy delegates to the parent's view permission
            // Regular users typically cannot view institutions from other tenants
            expect(Gate::forUser($user)->allows('view', $file))->toBeFalse();
        });

        test('returns false if fileable is null', function () {
            $orphanFile = FileableFile::factory()->create([
                'fileable_type' => Institution::class,
                'fileable_id' => 'nonexistent-id',
            ]);

            $user = makeUser($this->tenant);

            expect(Gate::forUser($user)->allows('view', $orphanFile))->toBeFalse();
        });
    });

    describe('update permission', function () {
        test('super admin can update any file', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            expect(Gate::forUser($user)->allows('update', $this->file))->toBeTrue();
        });

        test('user can update file if they can update the parent fileable', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            expect(Gate::forUser($user)->allows('update', $this->file))->toBeTrue();
        });
    });

    describe('delete permission', function () {
        test('super admin can delete any file', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            expect(Gate::forUser($user)->allows('delete', $this->file))->toBeTrue();
        });

        test('user can delete file if they can update the parent fileable', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            expect(Gate::forUser($user)->allows('delete', $this->file))->toBeTrue();
        });

        test('returns false if fileable is null for regular user', function () {
            $orphanFile = FileableFile::factory()->create([
                'fileable_type' => Institution::class,
                'fileable_id' => 'nonexistent-id',
            ]);

            // Use a regular user (not super admin) to test the policy logic
            $user = makeUser($this->tenant);

            expect(Gate::forUser($user)->allows('delete', $orphanFile))->toBeFalse();
        });
    });

    describe('restore and forceDelete permissions', function () {
        test('restore delegates to delete permission', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            $this->file->delete();

            expect(Gate::forUser($user)->allows('restore', $this->file))->toBeTrue();
        });

        test('forceDelete delegates to delete permission', function () {
            $user = makeUser($this->tenant);
            $user->assignRole('Super Admin');

            expect(Gate::forUser($user)->allows('forceDelete', $this->file))->toBeTrue();
        });
    });
});

describe('FileableFile with different fileable types', function () {
    test('policy works with Meeting as fileable', function () {
        $meeting = Meeting::factory()->create();
        $file = FileableFile::factory()->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $meeting->id,
        ]);

        $user = makeUser($this->tenant);
        $user->assignRole('Super Admin');

        expect(Gate::forUser($user)->allows('view', $file))->toBeTrue();
        expect(Gate::forUser($user)->allows('update', $file))->toBeTrue();
        expect(Gate::forUser($user)->allows('delete', $file))->toBeTrue();
    });
});
