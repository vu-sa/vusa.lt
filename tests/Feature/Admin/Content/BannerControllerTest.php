<?php

use App\Actions\Media\AddImageMedia;
use App\Actions\Media\SyncImageMedia;
use App\Models\Banner;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('spatieMediaLibrary');
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

    $this->banner = Banner::factory()->for($this->tenant)->create([
        'title' => 'Test baneris',
        'link_url' => 'https://example.com',
        'is_active' => true,
        'order' => 100, // Use a high order to avoid conflicts
    ]);
});

describe('unauthorized access', function (): void {
    test('cannot access index page', function (): void {
        asUser($this->user)
            ->get(route('banners.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)
            ->get(route('banners.create'))
            ->assertStatus(403);
    });

    test('cannot store banner', function (): void {
        $validData = getControllerTestData('Banner')['valid'];

        asUser($this->user)
            ->post(route('banners.store'), $validData)
            ->assertStatus(403);
    });

    test('cannot access edit page', function (): void {
        asUser($this->user)
            ->get(route('banners.edit', $this->banner))
            ->assertStatus(403);
    });

    test('cannot update banner', function (): void {
        $updateData = getControllerTestData('Banner')['valid'];

        asUser($this->user)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete banner', function (): void {
        asUser($this->user)
            ->delete(route('banners.destroy', $this->banner))
            ->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index page', function (): void {
        app(AddImageMedia::class)->execute($this->banner, UploadedFile::fake()->image('banner.webp', 800, 400), 'image');
        $image = $this->banner->fresh()->imageData('image');

        asUserWithInertia($this->admin)
            ->get(route('banners.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexBanner')
                ->has('banners')
                ->where('banners.data.0.id', $this->banner->id)
                ->where('banners.data.0.image_url', $image['thumb'])
            );
    });

    test('can access create page', function (): void {
        asUserWithInertia($this->admin)
            ->get(route('banners.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreateBanner')
            );
    });

    test('can store banner with valid data', function (): void {
        $validData = getControllerTestData('Banner')['valid'];
        $uniqueSuffix = time();
        $validData['title'] = 'Naujas baneris '.$uniqueSuffix;
        $staged = stageImage($this->admin);
        $validData['image_media'] = ['id' => $staged->id];

        $response = asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'title' => $validData['title'],
            'link_url' => $validData['link_url'],
            'is_active' => $validData['is_active'],
            'tenant_id' => $this->tenant->id,
        ]);

        $created = Banner::where('title', $validData['title'])->sole();
        $response->assertRedirectToRoute('banners.edit', $created)->assertSessionHas('success');
        asUser($this->admin)->get(route('banners.edit', $created))->assertOk();
        expect($created->getFirstMedia('image')->id)->toBe($staged->id);
    });

    test('can store banner without a logo — it renders as a text mark instead', function (): void {
        $validData = getControllerTestData('Banner')['valid'];
        $uniqueSuffix = time();
        $validData['title'] = 'Baneris be logotipo '.$uniqueSuffix;
        unset($validData['image_media']);

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302)
            ->assertRedirectToRoute('banners.edit', Banner::query()->latest('id')->firstOrFail())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'title' => $validData['title'],
        ]);
    });

    test('cannot store banner with invalid data', function (): void {
        $invalidData = getControllerTestData('Banner')['invalid'];

        asUser($this->admin)
            ->post(route('banners.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(getControllerValidationErrors('Banner'));
    });

    test('can access edit page', function (): void {
        asUserWithInertia($this->admin)
            ->get(route('banners.edit', $this->banner))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/EditBanner')
                ->has('banner')
                ->where('banner.id', $this->banner->id)
            );
    });

    test('keeps the original banner image and file when an update fails after image sync', function (): void {
        $banner = $this->banner;
        $original = app(AddImageMedia::class)->execute($banner, UploadedFile::fake()->image('original.png'), 'image');
        $banner->refresh();
        $staged = stageImage($this->admin)->refresh();
        $syncImage = app(SyncImageMedia::class);

        $this->mock(SyncImageMedia::class)->shouldReceive('fromValidated')->once()
            ->andReturnUsing(function (...$arguments) use ($syncImage, $banner, $staged): never {
                $syncImage->fromValidated(...$arguments);
                expect($banner->fresh()->getMedia('image')->modelKeys())->toBe([$staged->id]);

                throw new RuntimeException('Banner update failed after image sync.');
            });
        $this->withoutExceptionHandling();

        expect(fn () => asUser($this->admin)->patch(route('banners.update', $banner), [
            'title' => 'Atnaujintas baneris',
            'link_url' => 'https://example.org',
            'is_active' => false,
            'image_media' => ['id' => $staged->id],
        ]))->toThrow(RuntimeException::class, 'Banner update failed after image sync.')
            ->and($banner->fresh()->getAttributes())->toEqual($banner->getAttributes())
            ->and($staged->fresh()->getAttributes())->toEqual($staged->getAttributes())
            ->and($banner->fresh()->getMedia('image')->modelKeys())->toBe([$original->id]);
        Storage::disk($original->disk)->assertExists($original->getPathRelativeToRoot());
    });

    test('can update banner with valid data', function (): void {
        $updateData = getControllerTestData('Banner')['valid'];
        $updateData['title'] = 'Atnaujintas baneris';
        $staged = stageImage($this->admin);
        $updateData['image_media'] = ['id' => $staged->id];

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'title' => 'Atnaujintas baneris',
        ]);
        expect($this->banner->refresh()->getFirstMedia('image')->id)->toBe($staged->id);
    });

    test('cannot update banner with invalid data - missing title', function (): void {
        $invalidData = ['title' => ''];

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['title']);

        // Original data should remain unchanged
        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'title' => 'Test baneris',
        ]);
    });

    test('another person\'s staged upload cannot become the logo', function (): void {
        $updateData = getControllerTestData('Banner')['valid'];
        $updateData['image_media'] = ['id' => stageImage(makeUser($this->tenant))->id];

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertSessionHasErrors('image_media.id');

        expect($this->banner->refresh()->getMedia('image'))->toBeEmpty();
    });

    test('removing the logo clears it — the logo is optional', function (): void {
        $media = app(AddImageMedia::class)->execute($this->banner, UploadedFile::fake()->image('logo.png'), 'image');
        $this->assertModelExists($media);

        asUser($this->admin)->patch(route('banners.update', $this->banner), [
            ...getControllerTestData('Banner')['valid'],
            'image_media' => null,
        ])->assertRedirect()->assertSessionHas('success');

        expect($this->banner->fresh()->getMedia('image'))->toBeEmpty();
        $this->assertModelMissing($media);
    });

    test('can delete banner', function (): void {
        asUser($this->admin)
            ->delete(route('banners.destroy', $this->banner))
            ->assertStatus(302)
            ->assertRedirect(route('banners.index'))
            ->assertSessionHas('info');

        $this->assertSoftDeleted('banners', [
            'id' => $this->banner->id,
        ]);
    });
});

describe('banner functionality', function (): void {
    // Order used to be `rand(1, 10)`, which could collide with the
    // banners_order_padalinys_id_unique index. The model now appends to the end of the
    // tenant's existing banners, counting trashed ones since they still hold their slot.
    test('banner is created at the end of its tenant order', function (): void {
        $validData = getControllerTestData('Banner')['valid'];
        $validData['title'] = 'Banner with order';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        $banner = Banner::where('title', 'Banner with order')->first();
        $highestOtherOrder = Banner::withTrashed()
            ->where('tenant_id', $banner->tenant_id)
            ->whereKeyNot($banner->id)
            ->max('order') ?? 0;

        expect($banner->order)->toBe($highestOtherOrder + 1);
    });

    test('a trashed banner keeps its order slot so the unique index is not violated', function (): void {
        $trashed = Banner::factory()->for($this->tenant)->create(['order' => 500]);
        $trashed->delete();

        $validData = getControllerTestData('Banner')['valid'];
        $validData['title'] = 'Banner after trashed';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        expect(Banner::where('title', 'Banner after trashed')->first()->order)->toBe(501);
    });

    test('banner defaults is_active to false when not provided', function (): void {
        $validData = getControllerTestData('Banner')['valid'];
        unset($validData['is_active']);
        $validData['title'] = 'Inactive banner test';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        $this->assertDatabaseHas('banners', [
            'title' => 'Inactive banner test',
            'is_active' => 0,
        ]);
    });

    test('banner can have empty link_url', function (): void {
        $validData = getControllerTestData('Banner')['valid'];
        $validData['link_url'] = '';
        $validData['title'] = 'Banner without link';

        asUser($this->admin)
            ->post(route('banners.store'), $validData)
            ->assertStatus(302);

        $this->assertDatabaseHas('banners', [
            'title' => 'Banner without link',
            'link_url' => '',
        ]);
    });
});

describe('filtering and search', function (): void {
    beforeEach(function (): void {
        // Create additional banners for testing
        Banner::factory()->for($this->tenant)->create([
            'title' => 'Another banner',
            'is_active' => false,
        ]);
    });

    test('can filter banners by search term', function (): void {
        Banner::factory()->for($this->tenant)->create(['title' => 'Different banner']);
        asUserWithInertia($this->admin)->get(route('banners.index', ['search' => 'Test baneris']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('banners.data', 1)->where('banners.data.0.id', $this->banner->id));
    });

    test('can filter banners by active status', function (): void {
        asUserWithInertia($this->admin)
            ->get(route('banners.index', ['filters' => json_encode(['is_active' => '1'])]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('banners.meta.total', 1)
                ->where('banners.data.0.id', $this->banner->id)
            );
    });

    test('renders banner data with an explicit page size', function (): void {
        $this->banner->update(['title' => 'Pagination fixture one']);
        $banners = Banner::factory()->count(2)->for($this->tenant)->create(['title' => 'Pagination fixture']);
        $expected = $banners->modelKeys();
        $expected[] = $this->banner->id;
        $url = fn (int $page) => route('banners.index', ['search' => 'Pagination fixture', 'per_page' => 2, 'page' => $page]);

        $first = asUserWithInertia($this->admin)->get($url(1))->assertOk()
            ->assertJsonPath('props.banners.meta.per_page', 2)
            ->assertJsonPath('props.banners.meta.last_page', 2)
            ->assertJsonCount(2, 'props.banners.data');
        $second = asUserWithInertia($this->admin)->get($url(2))->assertOk()
            ->assertJsonPath('props.banners.meta.current_page', 2)
            ->assertJsonCount(1, 'props.banners.data');

        expect(collect([...$first->json('props.banners.data'), ...$second->json('props.banners.data')])->pluck('id')->all())
            ->toEqualCanonicalizing($expected);
    });
});

test('banner status can be changed without submitting the entire form', function (): void {
    asUser($this->admin)
        ->patch(route('banners.updateStatus', $this->banner), ['is_active' => false])
        ->assertRedirect();

    $this->assertDatabaseHas('banners', ['id' => $this->banner->id, 'is_active' => 0]);
    $this->assertDatabaseHas('banners', ['id' => $this->banner->id, 'title' => 'Test baneris']);
});

test('banner status change requires update permission', function (): void {
    asUser($this->user)
        ->patch(route('banners.updateStatus', $this->banner), ['is_active' => false])
        ->assertForbidden();
});

test('banner status change rejects invalid input', function (): void {
    asUser($this->admin)
        ->patch(route('banners.updateStatus', $this->banner), ['is_active' => 'invalid'])
        ->assertSessionHasErrors('is_active');

    $this->assertDatabaseHas('banners', ['id' => $this->banner->id, 'is_active' => 1]);
});

describe('tenant isolation', function (): void {
    beforeEach(function (): void {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherBanner = Banner::factory()->for($this->otherTenant)->create();
        $this->otherAdmin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->otherTenant);
    });

    test('user only sees banners from their tenant', function (): void {
        asUserWithInertia($this->admin)->get(route('banners.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('banners.data', 1)->where('banners.data.0.id', $this->banner->id));
    });

    test('cannot access other tenant banner', function (): void {
        asUser($this->admin)
            ->get(route('banners.edit', $this->otherBanner))
            ->assertStatus(403); // Authorization failure
    });

    test('cannot update other tenant banner', function (): void {
        $updateData = getControllerTestData('Banner')['valid'];

        asUser($this->admin)
            ->patch(route('banners.update', $this->otherBanner), $updateData)
            ->assertStatus(403); // Authorization failure
    });
});

describe('cache functionality', function (): void {
    test('banner cache is cleared when banner is saved', function (): void {
        $cache = Cache::tags(['banners']);
        $key = 'banner_groups_'.$this->tenant->id;
        $cache->put($key, 'cached payload', 60);
        expect($cache->has($key))->toBeTrue();

        $updateData = getControllerTestData('Banner')['valid'];
        $updateData['title'] = 'Updated for cache test';

        asUser($this->admin)
            ->patch(route('banners.update', $this->banner), $updateData)
            ->assertStatus(302);

        expect($cache->has($key))->toBeFalse();
        $this->assertDatabaseHas('banners', [
            'id' => $this->banner->id,
            'title' => 'Updated for cache test',
        ]);
    });

    test('banner cache is cleared when banner is deleted', function (): void {
        $cache = Cache::tags(['banners']);
        $key = 'banner_groups_'.$this->tenant->id;
        $cache->put($key, 'cached payload', 60);
        expect($cache->has($key))->toBeTrue();

        asUser($this->admin)
            ->delete(route('banners.destroy', $this->banner))
            ->assertStatus(302);

        expect($cache->has($key))->toBeFalse();
        $this->assertSoftDeleted('banners', [
            'id' => $this->banner->id,
        ]);
    });
});

test('initial banner collection puts active banners first', function (): void {
    Banner::factory()->for($this->tenant)->create(['title' => 'A first by title', 'is_active' => false]);
    asUser($this->admin)->get(route('banners.index'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('banners.data.0.id', $this->banner->id));
});
