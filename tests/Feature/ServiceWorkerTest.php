<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function (): void {
    $this->publicPath = public_path();
    $this->tempPublic = sys_get_temp_dir().'/sw-test-'.uniqid();
    File::ensureDirectoryExists($this->tempPublic.'/build');
    app()->usePublicPath($this->tempPublic);
});

afterEach(function (): void {
    app()->usePublicPath($this->publicPath);
    File::deleteDirectory($this->tempPublic);
});

test('serves the built worker from the site root so it can control /mano', function (): void {
    File::put($this->tempPublic.'/build/sw.js', 'self.addEventListener("push", () => {});');

    $response = $this->get('/sw.js');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/javascript')
        ->and($response->headers->get('Cache-Control'))->toContain('no-cache')
        ->and(file_get_contents($response->baseResponse->getFile()->getPathname()))->toContain('push');
});

test('returns 404 before the frontend has been built', function (): void {
    // The rendered 404 page needs a Vite manifest the temporary public path lacks.
    $this->withoutExceptionHandling();

    expect(fn () => $this->get('/sw.js'))->toThrow(NotFoundHttpException::class);
});
