<?php

use App\Actions\Media\AddImageMedia;
use App\Models\News;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('spatieMediaLibrary');
    $this->tenant = Tenant::query()->first();
});

describe('news API image fallback', function (): void {
    test('returns null image when news has no image', function (): void {
        News::query()->where('tenant_id', $this->tenant->id)->delete();

        // Create news without an image
        News::factory()->for($this->tenant)->create([
            'title' => 'Test News Without Image',
            'lang' => 'lt',
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->alias}/news?lang=lt");

        $response->assertSuccessful()
            ->assertJsonPath('data.0.image', null);
    });

    test('returns actual image when news has valid image', function (): void {
        News::query()->where('tenant_id', $this->tenant->id)->delete();

        // Create news with an image
        $news = News::factory()->for($this->tenant)->create([
            'title' => 'Test News With Image',
            'lang' => 'lt',
            'publish_time' => now()->subHours(1),
            'draft' => false,
        ]);
        app(AddImageMedia::class)->execute($news, UploadedFile::fake()->image('cover.jpg'), 'image');

        $response = $this->getJson("/api/v1/tenants/{$this->tenant->alias}/news?lang=lt");

        $response->assertSuccessful()
            ->assertJsonPath('data.0.image', $news->fresh()->getImageUrl());
    });

    test('returns correct news fields', function (): void {
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
            ->assertJsonPath('data.0.short', 'Test short description')
            ->assertJsonPath('data.0.permalink', 'test-news-permalink');
    });
});
