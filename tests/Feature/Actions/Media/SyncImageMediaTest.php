<?php

use App\Actions\Media\AddImageMedia;
use App\Actions\Media\SyncImageMedia;
use App\Models\Banner;
use App\Models\News;
use App\Models\PendingUpload;
use App\Models\Tenant;
use App\Services\Media\LegacyImageResolver;
use App\Support\Media\ImageData;
use App\Support\Media\LegacyImageUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
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
