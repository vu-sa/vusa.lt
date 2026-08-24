<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake();

    $this->tenant = Tenant::factory()->create(['type' => 'padalinys', 'alias' => 'test-tenant']);
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

    foreach (['files.read.padalinys', 'files.create.padalinys', 'files.update.padalinys', 'files.delete.padalinys'] as $permission) {
        if (! Permission::where('name', $permission)->exists()) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    $coordinatorRole = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $coordinatorRole->givePermissionTo(['files.read.padalinys', 'files.create.padalinys', 'files.update.padalinys', 'files.delete.padalinys']);

    $this->fileManager = User::factory()->create();
    $duty = Duty::factory()->create(['institution_id' => $this->institution->id, 'name' => 'File Manager']);
    $this->fileManager->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)]);
    $duty->assignRole('Communication Coordinator');

    $this->allowedPath = 'public/files/padaliniai/vusa'.$this->tenant->alias;
    Storage::makeDirectory('public/files/padaliniai/vusa'.$this->tenant->alias);
    Storage::put('public/files/padaliniai/vusa'.$this->tenant->alias.'/photo.jpg', 'jpg content');
    Storage::put('public/files/padaliniai/vusa'.$this->tenant->alias.'/graphic.png', 'png content');
    Storage::put('public/files/padaliniai/vusa'.$this->tenant->alias.'/document.pdf', 'pdf content');
    Storage::put('public/files/padaliniai/vusa'.$this->tenant->alias.'/notes.txt', 'txt content');
});

test('without an extensions filter, every file type is listed', function (): void {
    $response = asUser($this->fileManager)
        ->getJson(route('api.v1.admin.files.index', ['path' => $this->allowedPath]));

    $response->assertOk();
    $names = collect($response->json('data.files'))->pluck('name');
    expect($names)->toContain('photo.jpg', 'graphic.png', 'document.pdf', 'notes.txt');
});

test('an extensions filter excludes non-matching files (image picker no longer lists PDFs)', function (): void {
    $response = asUser($this->fileManager)
        ->getJson(route('api.v1.admin.files.index', ['path' => $this->allowedPath, 'extensions' => 'jpg,jpeg,png,gif,webp,svg']));

    $response->assertOk();
    $names = collect($response->json('data.files'))->pluck('name');
    expect($names)->toContain('photo.jpg', 'graphic.png')
        ->not->toContain('document.pdf', 'notes.txt');
});

test('extension matching is case-insensitive', function (): void {
    Storage::put('public/files/padaliniai/vusa'.$this->tenant->alias.'/UPPER.JPG', 'jpg content');

    $response = asUser($this->fileManager)
        ->getJson(route('api.v1.admin.files.index', ['path' => $this->allowedPath, 'extensions' => 'jpg']));

    $response->assertOk();
    expect(collect($response->json('data.files'))->pluck('name'))->toContain('UPPER.JPG');
});

test('an extension not in the allowlist is silently dropped rather than matching nothing unexpectedly', function (): void {
    // "exe" isn't in StoreFilesRequest::getAllowedExtensions(), so it's stripped from the
    // filter entirely — the request should not error, and (since no valid extension remains)
    // no files should match.
    $response = asUser($this->fileManager)
        ->getJson(route('api.v1.admin.files.index', ['path' => $this->allowedPath, 'extensions' => 'exe']));

    $response->assertOk();
    expect($response->json('data.files'))->toBeEmpty();
});

test('directories are never filtered out by an extensions filter', function (): void {
    Storage::makeDirectory('public/files/padaliniai/vusa'.$this->tenant->alias.'/subfolder');

    $response = asUser($this->fileManager)
        ->getJson(route('api.v1.admin.files.index', ['path' => $this->allowedPath, 'extensions' => 'jpg']));

    $response->assertOk();
    expect(collect($response->json('data.directories'))->pluck('name'))->toContain('subfolder');
});

test('unauthenticated users cannot list files', function (): void {
    $this->getJson(route('api.v1.admin.files.index', ['path' => $this->allowedPath]))
        ->assertUnauthorized();
});

describe('thumbnails', function (): void {
    beforeEach(function (): void {
        // The listing fixtures are text pretending to be images; the thumbnailer needs
        // something Intervention can actually decode.
        Storage::put(
            $this->allowedPath.'/real-photo.jpg',
            Image::createImage(1200, 800)->encodeUsingFileExtension('jpg')->toString()
        );
    });

    test('a stored image is served as a downscaled webp', function (): void {
        $response = asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg']));

        $response->assertOk()->assertHeader('Content-Type', 'image/webp');

        $thumbnail = Image::decodeBinary($response->streamedContent());
        expect($thumbnail->width())->toBe(320);
    });

    test('the requested width is honoured, so the hover preview gets a bigger copy', function (): void {
        $response = asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg', 'w' => 640]));

        $response->assertOk();
        expect(Image::decodeBinary($response->streamedContent())->width())->toBe(640);
    });

    test('an unlisted width falls back to the default instead of filling the cache disk', function (): void {
        $response = asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg', 'w' => 1337]));

        $response->assertOk();
        expect(Image::decodeBinary($response->streamedContent())->width())->toBe(320);
    });

    test('the derivative is written once and reused', function (): void {
        asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg']))
            ->assertOk();

        expect(Storage::files('thumbnails'))->toHaveCount(1);

        asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg']))
            ->assertOk();

        expect(Storage::files('thumbnails'))->toHaveCount(1);
    });

    test('a non-image is not thumbnailed', function (): void {
        asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/document.pdf']))
            ->assertNotFound();
    });

    test('a directory the user cannot view is refused', function (): void {
        Storage::put('public/files/padaliniai/vusaother/secret.jpg', 'whatever');

        asUser($this->fileManager)
            ->get(route('api.v1.admin.files.thumbnail', ['path' => 'public/files/padaliniai/vusaother/secret.jpg']))
            ->assertForbidden();
    });

    test('unauthenticated users get nothing', function (): void {
        // An <img> request carries no Accept: application/json, so the auth middleware
        // bounces it to login rather than answering 401 — either way, no image.
        $this->get(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg']))
            ->assertRedirect();

        $this->getJson(route('api.v1.admin.files.thumbnail', ['path' => $this->allowedPath.'/real-photo.jpg']))
            ->assertUnauthorized();
    });
});
