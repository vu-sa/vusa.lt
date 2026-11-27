<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->file = FileableFile::factory()->for($this->institution, 'fileable')->create();
});

test('any authenticated user can list files', function (): void {
    expect(makeUser($this->tenant)->can('viewAny', FileableFile::class))->toBeTrue();
});

test('view and editing follow the parent policy', function (string $parentType): void {
    if ($parentType === 'meeting') {
        $meeting = Meeting::factory()->hasAttached($this->institution)->create();
        $this->file = FileableFile::factory()->for($meeting, 'fileable')->create();
    }

    $reader = makeUser($this->tenant);
    $reader->givePermissionTo($parentType.'s.read.padalinys');

    $editor = makeUser($this->tenant);
    $editor->givePermissionTo($parentType.'s.update.padalinys');

    $otherTenantEditor = makeUser(Tenant::factory()->create());
    $otherTenantEditor->givePermissionTo([$parentType.'s.read.padalinys', $parentType.'s.update.padalinys']);

    expect($reader->can('view', $this->file))->toBeTrue()
        ->and($reader->can('update', $this->file))->toBeFalse()
        ->and($reader->can('delete', $this->file))->toBeFalse()
        ->and($editor->can('update', $this->file))->toBeTrue()
        ->and($editor->can('delete', $this->file))->toBeTrue()
        ->and($otherTenantEditor->can('view', $this->file))->toBeFalse()
        ->and($otherTenantEditor->can('update', $this->file))->toBeFalse()
        ->and($otherTenantEditor->can('delete', $this->file))->toBeFalse();
})->with(['institution', 'meeting']);

test('access is denied when the file has no parent', function (): void {
    $orphan = FileableFile::factory()->create([
        'fileable_type' => MorphMap::alias(Institution::class),
        'fileable_id' => 'nonexistent-id',
    ]);
    $user = makeUser($this->tenant);
    $user->givePermissionTo(['institutions.read.padalinys', 'institutions.update.padalinys']);

    expect($user->can('view', $orphan))->toBeFalse()
        ->and($user->can('update', $orphan))->toBeFalse()
        ->and($user->can('delete', $orphan))->toBeFalse();
});
