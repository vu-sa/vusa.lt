<?php

namespace Tests;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $seed = true;

    protected $seeder = TestSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['inertia.ssr.enabled' => false]);
    }

    protected function createTestResponse($response, $request)
    {
        $testResponse = parent::createTestResponse($response, $request);

        // Inertia's assertion helper still reads view data for JSON visits.
        if ($response instanceof JsonResponse && $response->headers->get('X-Inertia') === 'true') {
            $response->original = view('app', ['page' => $testResponse->json()]);
        }

        return $testResponse;
    }
}
