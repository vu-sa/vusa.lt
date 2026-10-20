<?php

use App\Models\ContentPart;
use App\Support\Media\ImageData;
use App\Support\Media\ResizableImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake();
});

function storeFileManagerImage(string $path, int $width = 3000, int $height = 2000): void
{
    $image = UploadedFile::fake()->image('photo.jpg', $width, $height);
    Storage::put($path, file_get_contents($image->getRealPath()));
}

test('serves a cacheable WebP copy scaled to the requested width', function (): void {
    storeFileManagerImage('public/files/padaliniai/My photo.jpg');

    $response = $this->get('/img/900/files/padaliniai/My%20photo.jpg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/webp')
        ->assertCookieMissing(config('session.cookie'));

    expect(getimagesizefromstring(file_get_contents($response->baseResponse->getFile()->getPathname()))[0])->toBe(900)
        ->and($response->headers->get('Cache-Control'))->toContain('public');
});

test('keeps transparent artwork lossless', function (): void {
    $image = imagecreatetruecolor(800, 200);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    ob_start();
    imagepng($image);
    Storage::put('public/files/logo.png', (string) ob_get_clean());

    $response = $this->get('/img/400/files/logo.png')->assertOk();

    expect(substr(file_get_contents($response->baseResponse->getFile()->getPathname()), 12, 4))->toBe('VP8L');
});

test('never enlarges a source smaller than the requested width', function (): void {
    storeFileManagerImage('public/files/small.jpg', 600, 400);

    $response = $this->get('/img/2400/files/small.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    expect(getimagesizefromstring(file_get_contents($response->baseResponse->getFile()->getPathname()))[0])->toBe(600)
        ->and(file_get_contents($response->baseResponse->getFile()->getPathname()))->toBe(Storage::get('public/files/small.jpg'))
        ->and(Storage::allFiles('thumbnails'))->toBeEmpty();
});

test('resolves legacy uploads urls the way RewriteUploadsUrl does', function (): void {
    storeFileManagerImage('public/files/news/old.jpg');

    $this->get('/img/400/news/old.jpg')->assertOk();
});

test('rejects anything but an existing public image at an allowed width', function (string $url): void {
    storeFileManagerImage('public/files/photo.jpg');
    Storage::put('public/files/report.pdf', 'pdf');
    Storage::put('private.jpg', 'secret');

    $this->get($url)->assertNotFound();
})->with([
    'missing file' => '/img/400/files/missing.jpg',
    'non-image' => '/img/400/files/report.pdf',
    'traversal' => '/img/400/files/%2e%2e/%2e%2e/private.jpg',
    'media library' => '/img/400/media/1/photo.jpg',
]);

test('an unlisted width generates nothing', function (): void {
    storeFileManagerImage('public/files/photo.jpg');

    $response = $this->get('/img/1337/files/photo.jpg');

    expect($response->headers->get('Content-Type'))->not->toBe('image/webp')
        ->and(Storage::allFiles('thumbnails'))->toBeEmpty();
});

test('a replaced source gets a fresh copy', function (): void {
    storeFileManagerImage('public/files/photo.jpg', 2000, 1000);
    $first = $this->get('/img/400/files/photo.jpg')->baseResponse->getFile()->getPathname();

    storeFileManagerImage('public/files/photo.jpg', 1000, 1000);
    touch(Storage::path('public/files/photo.jpg'), time() + 60);
    $second = $this->get('/img/400/files/photo.jpg')->baseResponse->getFile()->getPathname();

    expect($second)->not->toBe($first)
        ->and(getimagesize($second)[1])->toBe(400);
});

test('rich text gets srcset for same-site uploads only, keeping authored markup', function (): void {
    storeFileManagerImage('public/files/My photo.png', 1600, 1067);
    $html = '<p>A &amp; B</p><img src="/uploads/files/My photo.png" alt="Autorius" width="240" data-align="left">'
        .'<img src="https://example.com/uploads/files/a.png"><img src="/uploads/media/1/a.webp">';

    $rendered = ResizableImage::withSrcset($html);

    expect($rendered)->toContain('<p>A &amp; B</p>', 'alt="Autorius"', 'data-align="left"', 'sizes="min(240px, 100vw)"',
        'srcset="/img/400/files/My%20photo.png 400w', '<img src="https://example.com/uploads/files/a.png">', '<img src="/uploads/media/1/a.webp">')
        ->and(substr_count($rendered, 'srcset='))->toBe(1)
        ->and(ResizableImage::withSrcset('<p>No images</p>'))->toBe('<p>No images</p>')
        ->and($rendered)->toContain('/uploads/files/My%20photo.png 1600w')->not->toContain('2400w', '3840w', '/img/1600/');
});

test('CMS blocks serialize source-checked candidates including a nonstandard native width', function (): void {
    storeFileManagerImage('public/files/hero.jpg', 1200, 800);
    $part = new ContentPart(['type' => 'hero-carousel', 'json_content' => [
        ['imageSrc' => '/uploads/files/hero.jpg'], ['imageSrc' => '/uploads/files/missing.jpg'],
    ]]);

    expect($part->toArray()['image_srcsets'])->toBe([
        '/uploads/files/hero.jpg' => '/img/400/files/hero.jpg 400w, /img/900/files/hero.jpg 900w, /uploads/files/hero.jpg 1200w',
    ])
        ->and(ImageData::fromLegacy('/uploads/files/hero.jpg')['srcset'])->toBe($part->toArray()['image_srcsets']['/uploads/files/hero.jpg'])
        ->and(ResizableImage::withSrcset('<img src="/uploads/files/missing.jpg">'))->toBe('<img src="/uploads/files/missing.jpg">');
});
