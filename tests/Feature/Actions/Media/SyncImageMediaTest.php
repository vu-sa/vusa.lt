<?php

use App\Actions\Media\AddImageMedia;
use App\Actions\Media\SyncImageMedia;
use App\Models\Banner;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\PendingUpload;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Media\LegacyImageResolver;
use App\Support\Media\ImageData;
use App\Support\Media\LegacyImageUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    (require base_path('database/migrations/2026_10_19_192000_drop_legacy_image_columns.php'))->down();
    Storage::fake('spatieMediaLibrary');
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->banner = Banner::factory()->for($this->tenant)->create(['image_url' => null]);
});

test('a staged upload moves onto the record without copying its files', function (): void {
    $staged = stageImage($this->admin);
    $path = $staged->getPathRelativeToRoot();

    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $staged->id, 'alt' => 'Partnerio logotipas'], $this->admin);

    $media = $this->banner->refresh()->getFirstMedia('image');

    expect($media->id)->toBe($staged->id)
        ->and($media->getCustomProperty('alt'))->toBe('Partnerio logotipas')
        ->and(PendingUpload::query()->count())->toBe(0)
        ->and($this->banner->image_url)->toContain("/{$staged->id}/");
    Storage::disk('spatieMediaLibrary')->assertExists($path);
});

test('a new image replaces the previous one', function (): void {
    $first = stageImage($this->admin);
    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $first->id], $this->admin);

    $second = stageImage($this->admin, 'new.png');
    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $second->id], $this->admin);

    expect($this->banner->refresh()->getMedia('image')->pluck('id')->all())->toBe([$second->id])
        ->and(Media::query()->find($first->id))->toBeNull();
});

test('a rolled-back replacement keeps the previous image\'s files', function (): void {
    $first = stageImage($this->admin);
    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $first->id], $this->admin);
    $path = $first->refresh()->getPathRelativeToRoot();

    DB::beginTransaction();
    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => stageImage($this->admin, 'new.png')->id], $this->admin);
    DB::rollBack();

    expect($this->banner->refresh()->getFirstMedia('image')->id)->toBe($first->id);
    Storage::disk('spatieMediaLibrary')->assertExists($path);
});

test('another person\'s staged upload cannot be claimed', function (): void {
    $staged = stageImage(makeUser($this->tenant));

    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $staged->id], $this->admin);
})->throws(ValidationException::class);

test('an image of a record the actor cannot see is not copied', function (): void {
    $otherBanner = Banner::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->first())->create();
    $foreign = app(AddImageMedia::class)->execute($otherBanner, UploadedFile::fake()->image('x.png'), 'image');

    expect(fn () => app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $foreign->id], $this->admin))
        ->toThrow(ValidationException::class);

    expect($otherBanner->getMedia('image'))->toHaveCount(1);
});

test('an image of a record the actor can see is copied, not moved', function (): void {
    $source = Banner::factory()->for($this->tenant)->create();
    $original = app(AddImageMedia::class)->execute($source, UploadedFile::fake()->image('x.png'), 'image');

    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $original->id], $this->admin);

    $copy = $this->banner->refresh()->getFirstMedia('image');

    expect($copy->id)->not->toBe($original->id)
        ->and($source->refresh()->getFirstMedia('image')->id)->toBe($original->id);
});

test('null removes the image and its cached url', function (): void {
    $staged = stageImage($this->admin);
    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $staged->id], $this->admin);

    app(SyncImageMedia::class)->execute($this->banner, 'image', null, $this->admin);

    expect($this->banner->refresh()->getMedia('image'))->toBeEmpty()
        ->and($this->banner->image_url)->toBeNull();
});

test('without an id the current image is kept and only its properties change', function (): void {
    $staged = stageImage($this->admin);
    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => $staged->id], $this->admin);

    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => null, 'alt' => 'Naujas aprašas'], $this->admin);

    $media = $this->banner->refresh()->getFirstMedia('image');

    expect($media->id)->toBe($staged->id)
        ->and($media->getCustomProperty('alt'))->toBe('Naujas aprašas');
});

test('without an id a legacy image is imported before its properties change', function (): void {
    $uploads = sys_get_temp_dir().'/legacy-uploads-'.Str::random(8);
    File::ensureDirectoryExists($uploads.'/banners');
    File::put($uploads.'/banners/partner.png', UploadedFile::fake()->image('partner.png', 300, 100)->getContent());
    app()->instance(LegacyImageResolver::class, new LegacyImageResolver($uploads, $uploads));
    $this->banner->update(['image_url' => '/uploads/banners/partner.png']);

    app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => null, 'alt' => 'Partneris'], $this->admin);

    $media = $this->banner->refresh()->getFirstMedia('image');

    expect($media->getCustomProperty('legacy_source'))->toBe('/uploads/banners/partner.png')
        ->and($media->getCustomProperty('alt'))->toBe('Partneris')
        ->and(File::exists($uploads.'/banners/partner.png'))->toBeTrue();

    File::deleteDirectory($uploads);
});

test('saving an unchanged bare news image imports it instead of removing it', function (): void {
    $uploads = sys_get_temp_dir().'/legacy-news-'.Str::random(8);
    $filename = str_repeat('a', 40).'.jpeg';
    File::ensureDirectoryExists($uploads.'/files/news');
    File::put($uploads.'/files/news/'.$filename, UploadedFile::fake()->image('news.jpeg')->getContent());
    app()->instance(LegacyImageResolver::class, new LegacyImageResolver($uploads, $uploads));
    try {
        $news = News::factory()->for($this->tenant)->create(['image' => $filename, 'image_author' => 'Jonas']);
        $ref = $news->imageData('image');
        expect($ref['url'])->toBe('/uploads/files/news/'.$filename)
            ->and($news->getRawOriginal('image'))->toBe($filename);
        app(SyncImageMedia::class)->execute($news, 'image', $ref, $this->admin);
        $media = $news->refresh()->getFirstMedia('image');
        expect($media)->not->toBeNull()
            ->and($media->getCustomProperty('legacy_source'))->toBe($filename)
            ->and($media->getCustomProperty('author'))->toBe('Jonas');
        expect(ImageData::fromLegacy(LegacyImageUrl::PLACEHOLDER))->toBeNull();
    } finally {
        File::deleteDirectory($uploads);
    }
});

test('an undecodable legacy file leaves the record saveable and its url in place', function (): void {
    $uploads = sys_get_temp_dir().'/legacy-broken-'.Str::random(8);
    File::ensureDirectoryExists($uploads.'/banners');
    File::put($uploads.'/banners/broken.png', 'not an image');
    app()->instance(LegacyImageResolver::class, new LegacyImageResolver($uploads, $uploads));
    try {
        $this->banner->update(['image_url' => '/uploads/banners/broken.png']);

        app(SyncImageMedia::class)->execute($this->banner, 'image', ['id' => null, 'alt' => 'Partneris'], $this->admin);

        expect($this->banner->refresh()->getMedia('image'))->toBeEmpty()
            ->and($this->banner->image_url)->toBe('/uploads/banners/broken.png');
    } finally {
        File::deleteDirectory($uploads);
    }
});

test('a legacy image that cannot be imported keeps its edits in the legacy columns', function (): void {
    $news = News::factory()->for($this->tenant)->create(['image' => 'https://example.com/photo.jpg', 'image_author' => 'Jonas']);

    app(SyncImageMedia::class)->execute($news, 'image', ['id' => null, 'author' => 'Ona'], $this->admin);

    $news->refresh();

    expect($news->getMedia('image'))->toBeEmpty()
        ->and($news->image)->toBe('https://example.com/photo.jpg')
        ->and($news->imageData('image')['author'])->toBe('Ona');
});

test('a non-super-admin can copy an assignment photo only within their granted tenant scope', function (): void {
    $role = Role::create(['name' => 'Assignment photo reader', 'guard_name' => 'web']);
    $role->givePermissionTo('dutiables.read.padalinys');
    $duty = $this->admin->duties()->first();
    $duty->assignRole($role);
    $source = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create();
    $destination = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create();
    $original = app(AddImageMedia::class)->execute($source, UploadedFile::fake()->image('assignment.png'), 'photo');

    app(SyncImageMedia::class)->execute($destination, 'photo', ['id' => $original->id], $this->admin);

    expect($this->admin->isSuperAdmin())->toBeFalse()
        ->and($destination->fresh()->getFirstMedia('photo')->id)->not->toBe($original->id)
        ->and($source->fresh()->getFirstMedia('photo')->id)->toBe($original->id);

    $foreign = Dutiable::factory()->create();
    $foreignImage = app(AddImageMedia::class)->execute($foreign, UploadedFile::fake()->image('foreign.png'), 'photo');
    expect(fn () => app(SyncImageMedia::class)->execute($destination, 'photo', ['id' => $foreignImage->id], $this->admin))
        ->toThrow(ValidationException::class);
});

describe('after legacy image columns are dropped', function (): void {
    beforeEach(function (): void {
        (require base_path('database/migrations/2026_10_19_192000_drop_legacy_image_columns.php'))->up();
    });

    test('image upload, metadata edits, replacement and removal still work', function (string $model, string $collection): void {
        $owner = $model::factory()->for($this->tenant)->create();
        $first = stageImage($this->admin);
        $sync = app(SyncImageMedia::class);

        $sync->execute($owner, $collection, ['id' => $first->id, 'author' => 'Jonas'], $this->admin);

        expect($owner->fresh()->imageData($collection)['id'])->toBe($first->id);
        expect($owner->fresh()->imageData($collection)['author'])->toBe('Jonas');
        Storage::disk('spatieMediaLibrary')->assertExists($first->fresh()->getPathRelativeToRoot());

        $sync->execute($owner, $collection, ['id' => null, 'author' => 'Ona', 'focal_point' => '40% 60%'], $this->admin);

        expect($owner->fresh()->imageData($collection)['author'])->toBe('Ona');
        expect($owner->fresh()->imageData($collection)['focal_point'])->toBe('40% 60%');

        $second = stageImage($this->admin, 'replacement.png');
        $sync->execute($owner, $collection, ['id' => $second->id], $this->admin);

        expect($owner->fresh()->getMedia($collection)->modelKeys())->toBe([$second->id]);
        $this->assertModelMissing($first);

        $sync->execute($owner, $collection, null, $this->admin);

        expect($owner->fresh()->imageData($collection))->toBeNull();
        $this->assertModelMissing($second);
    })->with([
        'news' => [News::class, 'image'],
        'banner' => [Banner::class, 'image'],
        'institution' => [Institution::class, 'image'],
        'page' => [Page::class, 'featured_image'],
    ]);

    test('metadata without media does not write retired columns', function (string $model): void {
        $owner = $model::factory()->for($this->tenant)->create();

        app(SyncImageMedia::class)->execute($owner, 'image', ['id' => null, 'author' => 'Ona', 'focal_point' => '40% 60%'], $this->admin);

        expect($owner->fresh()->imageData('image'))->toBeNull();
        $this->assertModelExists($owner);
    })->with([News::class, Institution::class]);

    test('legacy metadata still saves in retained avatar columns', function (): void {
        $user = User::factory()->create(['profile_photo_path' => 'https://example.com/photo.jpg']);

        app(SyncImageMedia::class)->execute($user, 'profile_photo', ['id' => null, 'focal_point' => '40% 60%'], $this->admin);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'profile_photo_focal_point' => '40% 60%']);
        expect($user->fresh()->getMedia('profile_photo'))->toBeEmpty();
    });
});
