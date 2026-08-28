<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

describe('batch upload', function (): void {
    test('a whole batch is stored in one request', function (): void {
        $response = asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
            'path' => $this->allowedPath,
            'files' => [
                ['file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')],
                ['file' => UploadedFile::fake()->create('notes.csv', 5, 'text/csv')],
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        expect($response->json('data.uploaded'))->toHaveCount(2)
            ->and($response->json('data.failed'))->toBe([]);

        Storage::assertExists($this->allowedPath.'/report.pdf');
        Storage::assertExists($this->allowedPath.'/notes.csv');
    });

    test('images and other files travel together instead of racing as two visits', function (): void {
        $response = asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
            'path' => $this->allowedPath,
            'files' => [
                ['file' => UploadedFile::fake()->image('picture.png', 40, 40)],
                ['file' => UploadedFile::fake()->create('handout.pdf', 4, 'application/pdf')],
            ],
        ]);

        $response->assertOk();

        expect($response->json('data.uploaded'))->toHaveCount(2);

        // Rasterisable images are re-encoded to WebP; everything else keeps its name.
        Storage::assertExists($this->allowedPath.'/picture.webp');
        Storage::assertExists($this->allowedPath.'/handout.pdf');
    });

    test('a name collision is suffixed rather than overwritten', function (): void {
        asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
            'path' => $this->allowedPath,
            'files' => [['file' => UploadedFile::fake()->create('document.pdf', 3, 'application/pdf')]],
        ])->assertOk();

        expect(Storage::get($this->allowedPath.'/document.pdf'))->toBe('pdf content')
            ->and(collect(Storage::files($this->allowedPath))
                ->filter(fn (string $p) => str_contains($p, 'document_'))
            )->toHaveCount(1);
    });

    test('a directory the user cannot write is refused', function (): void {
        asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
            'path' => 'public/files/padaliniai/vusaother',
            'files' => [['file' => UploadedFile::fake()->create('sneaky.pdf', 3, 'application/pdf')]],
        ])->assertForbidden();

        Storage::assertMissing('public/files/padaliniai/vusaother/sneaky.pdf');
    });

    test('a disallowed extension is rejected', function (): void {
        asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
            'path' => $this->allowedPath,
            'files' => [['file' => UploadedFile::fake()->create('payload.exe', 3)]],
        ])->assertStatus(422);

        Storage::assertMissing($this->allowedPath.'/payload.exe');
    });

    test('an oversized file is rejected', function (): void {
        asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
            'path' => $this->allowedPath,
            'files' => [['file' => UploadedFile::fake()->create('huge.pdf', 60 * 1024, 'application/pdf')]],
        ])->assertStatus(422);
    });

    test('unauthenticated users cannot upload', function (): void {
        $this->postJson(route('api.v1.admin.files.store'), [
            'path' => $this->allowedPath,
            'files' => [['file' => UploadedFile::fake()->create('anon.pdf', 3, 'application/pdf')]],
        ])->assertUnauthorized();
    });
});

describe('recursive search', function (): void {
    beforeEach(function (): void {
        Storage::put($this->allowedPath.'/nested/deep/summer-camp.pdf', 'nested');
        Storage::put($this->allowedPath.'/nested/camp-notes.txt', 'nested');
    });

    test('files several folders down are found', function (): void {
        $response = asUser($this->fileManager)
            ->getJson(route('api.v1.admin.files.search', ['q' => 'camp', 'path' => $this->allowedPath]));

        $response->assertOk();

        $names = collect($response->json('data.files'))->pluck('name');
        expect($names)->toContain('summer-camp.pdf', 'camp-notes.txt');
    });

    test('each hit carries the folder it lives in', function (): void {
        $response = asUser($this->fileManager)
            ->getJson(route('api.v1.admin.files.search', ['q' => 'summer', 'path' => $this->allowedPath]));

        expect($response->json('data.files.0.directory'))->toBe($this->allowedPath.'/nested/deep');
    });

    test('the extensions filter applies to results', function (): void {
        $response = asUser($this->fileManager)->getJson(route('api.v1.admin.files.search', [
            'q' => 'camp',
            'path' => $this->allowedPath,
            'extensions' => 'pdf',
        ]));

        $names = collect($response->json('data.files'))->pluck('name');
        expect($names)->toContain('summer-camp.pdf')->not->toContain('camp-notes.txt');
    });

    test('a directory the user cannot view is never walked', function (): void {
        Storage::put('public/files/padaliniai/vusaother/camp-secret.pdf', 'other tenant');

        $response = asUser($this->fileManager)
            ->getJson(route('api.v1.admin.files.search', ['q' => 'camp', 'path' => 'public/files']));

        // The root itself is off-limits to a tenant-scoped user, so the walk never starts.
        $response->assertForbidden();
    });

    test('a sibling tenant folder is skipped when the walk does start', function (): void {
        Storage::put('public/files/padaliniai/vusaother/camp-secret.pdf', 'other tenant');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));

        $mine = asUser($this->fileManager)
            ->getJson(route('api.v1.admin.files.search', ['q' => 'camp', 'path' => $this->allowedPath]));

        expect(collect($mine->json('data.files'))->pluck('name'))
            ->not->toContain('camp-secret.pdf');
    });

    test('a query shorter than two characters is rejected', function (): void {
        asUser($this->fileManager)
            ->getJson(route('api.v1.admin.files.search', ['q' => 'c', 'path' => $this->allowedPath]))
            ->assertStatus(422);
    });

    test('unauthenticated users cannot search', function (): void {
        $this->getJson(route('api.v1.admin.files.search', ['q' => 'camp']))
            ->assertUnauthorized();
    });
});

test('a TipTap content path resolves to the tenant folder instead of being authorized as one', function (): void {
    // The editor posts `content/Y/m`, which has no `padaliniai/` segment — authorizing it as a
    // literal directory would 403 every tenant-scoped user out of pasting an image.
    $response = asUser($this->fileManager)->postJson(route('api.v1.admin.files.store'), [
        'path' => 'content/'.date('Y/m'),
        'files' => [['file' => UploadedFile::fake()->create('pasted.pdf', 4, 'application/pdf')]],
    ]);

    $response->assertOk();

    Storage::assertExists('public/files/padaliniai/vusa'.$this->tenant->alias.'/content/'.date('Y/m').'/pasted.pdf');
    expect($response->json('data.uploaded.0.url'))
        ->toStartWith('/uploads/files/padaliniai/vusa'.$this->tenant->alias.'/content/');
});
