<?php

use App\Actions\Media\AddImageMedia;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use App\Support\Media\ImageConversions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use Inertia\Inertia;

pest()->use(RefreshDatabase::class);

test('the browser loads a generated image in the public page at phone and desktop widths', function (): void {
    config(['filesystems.disks.spatieMediaLibrary.url' => '/__image-audit', 'media-library.queue_conversions_by_default' => false]);
    Storage::fake('spatieMediaLibrary', ['url' => '/__image-audit']);
    $record = Page::factory()->create();
    $media = app(AddImageMedia::class)->execute($record, UploadedFile::fake()->image('responsive.jpg', 2400, 1600), 'featured_image');
    $record->unsetRelation('media');
    $image = $record->imageData('featured_image');

    foreach (['', ...array_keys(ImageConversions::WIDTHS)] as $conversion) {
        $path = $media->getPathRelativeToRoot($conversion);
        Route::get('/'.Uri::of($media->getUrl($conversion))->path(), fn () => response(Storage::disk('spatieMediaLibrary')->get($path), 200, ['Content-Type' => 'image/webp']));
    }
    Route::get('/lt/_image-audit', fn () => Inertia::render('Public/ContentPage', [
        'navigationItemId' => 0,
        'tenant' => Tenant::query()->first()->only(['id', 'alias', 'shortname']),
        'page' => ['title' => 'Image delivery', 'featured_image' => $image['url'], 'featured_image_media' => $image],
    ]))->middleware('web');

    $page = visitPublicSubdomain('www', '/lt/_image-audit');
    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 1000)->assertPresent('[data-slot=media-image][srcset]');
        $page->script('new Promise(requestAnimationFrame).then(() => new Promise(requestAnimationFrame))');
        $page->page()->waitForFunction('(() => { const image = document.querySelector("main [data-slot=media-image][srcset]"); return image?.complete && image.naturalWidth > 0; })()');
        $page->script('document.querySelector("main [data-slot=media-image][srcset]").decode()');
        $selected = $page->script('document.querySelector("main [data-slot=media-image][srcset]").currentSrc');
        expect($page->script('document.querySelector("main [data-slot=media-image][srcset]").naturalWidth'))->toBeGreaterThan(0)
            ->and($selected)->toContain('/conversions/')->not->toContain('undefined');
    }
    $page->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: 'responsive-image-delivery');
});

test('a CMS hero advertises its real source width and selects the original on a large screen', function (): void {
    Storage::fake();
    $file = UploadedFile::fake()->image('hero.jpg', 1600, 1067);
    Storage::put('public/files/hero.jpg', file_get_contents($file->getRealPath()));
    Route::get('/uploads/files/hero.jpg', fn () => response()->file(Storage::path('public/files/hero.jpg')));
    $part = new ContentPart(['type' => 'hero-carousel', 'json_content' => [
        ['imageSrc' => '/uploads/files/hero.jpg', 'imageAlt' => 'CMS hero', 'title' => 'Image delivery'],
    ], 'options' => []]);
    Route::get('/lt/_cms-image-audit', fn () => Inertia::render('Public/ContentPage', [
        'navigationItemId' => 0,
        'tenant' => Tenant::query()->first()->only(['id', 'alias', 'shortname']),
        'page' => ['title' => 'CMS image delivery', 'layout' => 'wide', 'content' => ['parts' => [$part->toArray()]]],
    ]))->middleware('web');

    $page = visitPublicSubdomain('www', '/lt/_cms-image-audit')->resize(1440, 1000)
        ->assertPresent('[data-slot=media-image][srcset]');
    $page->page()->waitForFunction('(() => { const image = document.querySelector("main [data-slot=media-image]"); return image?.complete && image.naturalWidth > 0; })()');
    expect($page->script('document.querySelector("main [data-slot=media-image]").srcset'))
        ->toBe('/img/400/files/hero.jpg 400w, /img/900/files/hero.jpg 900w, /uploads/files/hero.jpg 1600w')
        ->and($page->script('document.querySelector("main [data-slot=media-image]").currentSrc'))->toContain('/uploads/files/hero.jpg');
    $page->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: 'cms-hero-image-delivery');
});
