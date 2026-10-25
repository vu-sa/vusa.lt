<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the Vite-built worker from the site root: a worker's scope cannot reach above its own URL,
 * so /build/sw.js could never control /mano.
 */
class ServiceWorkerController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = public_path('build/sw.js');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
