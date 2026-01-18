<?php

use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
});

describe('news API image fallback', function () {
    test('returns fallback image when news has no image', function () {
        News::query()->where('tenant_id', $this->tenant->id)->delete();

        // Create news without an image
        News::factory()->for($this->tenant)->create([
            'title' => 'Test News Without Image',
            'lang' => 'lt',
            'image' => null,
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->alias}/news?lang=lt");

        $response->assertSuccessful()
            ->assertJsonPath('data.0.image', '/images/icons/naujienu_foto.png');
    });

    test('returns actual image when news has valid image', function () {
        News::query()->where('tenant_id', $this->tenant->id)->delete();

        // Create news with an external image URL
        News::factory()->for($this->tenant)->create([
            'title' => 'Test News With Image',
            'lang' => 'lt',
            'image' => 'https://example.com/image.jpg',
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->alias}/news?lang=lt");

        $response->assertSuccessful()
            ->assertJsonPath('data.0.image', 'https://example.com/image.jpg');
    });

    test('returns correct news fields', function () {
        News::query()->where('tenant_id', $this->tenant->id)->delete();

        News::factory()->for($this->tenant)->create([
            'title' => 'Test News Title',
            'short' => 'Test short description',
            'lang' => 'lt',
            'permalink' => 'test-news-permalink',
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->alias}/news?lang=lt");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'lang', 'short', 'publish_time', 'permalink', 'image'],
                ],
            ])
            ->assertJsonPath('data.0.title', 'Test News Title')
            ->assertJsonPath('data.0.permalink', 'test-news-permalink');
    });
});
