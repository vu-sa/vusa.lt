<?php

use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->newsManager = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
});

describe('News Tag Functionality', function (): void {

    test('news can be created with tags via controller', function (): void {
        $tag1 = Tag::factory()->create(['name' => ['lt' => 'Testas 1', 'en' => 'Test 1']]);
        $tag2 = Tag::factory()->create(['name' => ['lt' => 'Testas 2', 'en' => 'Test 2']]);

        $newsData = [
            'title' => 'Test News',
            'permalink' => 'test-news',
            'content' => [
                'parts' => [
                    [
                        'type' => 'tiptap', // Use valid content part type
                        'json_content' => ['lt' => 'News content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'lang' => 'lt',
            'image' => 'test-image.jpg',
            'publish_time' => now()->toISOString(),
            'short' => 'Short description',
            'tags' => [$tag1->id, $tag2->id],
            'tenant_id' => $this->tenant->id, // Ensure news is created for correct tenant
        ];

        $response = asUser($this->newsManager)->post(route('news.store'), $newsData);

        $news = News::where('title', 'Test News')->firstOrFail();
        $response->assertRedirect(route('news.edit', $news))->assertSessionHas('success');

        expect($news->tags)->toHaveCount(2)
            ->and($news->tags->pluck('id')->toArray())->toEqual([$tag1->id, $tag2->id]);
    });

    test('news tags can be updated via controller', function (): void {
        $news = News::factory()->state(['tenant_id' => $this->tenant->id])->create(); // Ensure news belongs to same tenant
        $tag1 = Tag::factory()->create(['name' => ['lt' => 'Testas 1', 'en' => 'Test 1']]);
        $tag2 = Tag::factory()->create(['name' => ['lt' => 'Testas 2', 'en' => 'Test 2']]);
        $tag3 = Tag::factory()->create(['name' => ['lt' => 'Testas 3', 'en' => 'Test 3']]);

        // Initial tags
        $news->tags()->sync([$tag1->id, $tag2->id]);

        $updateData = [
            'title' => $news->title,
            'lang' => $news->lang,
            'short' => $news->short,
            'image' => $news->image,
            'publish_time' => $news->publish_time->timestamp * 1000, // Convert to milliseconds as expected by the frontend
            'permalink' => $news->permalink,
            'content' => [
                'parts' => [
                    [
                        'id' => $news->content->parts->first()->id, // Include existing content part ID
                        'type' => 'tiptap', // Use valid content part type
                        'json_content' => ['lt' => 'Updated content'],
                        'options' => [],
                        'order' => 1,
                    ],
                ],
            ],
            'tags' => [$tag2->id, $tag3->id], // Update tags
        ];

        $response = asUser($this->newsManager)
            ->patch(route('news.update', $news), $updateData)
            ->assertSessionHas('success');

        $news->refresh();
        expect($news->tags)->toHaveCount(2)
            ->and($news->tags->pluck('id')->sort()->values()->toArray())->toEqual(collect([$tag2->id, $tag3->id])->sort()->values()->toArray());
    });
});
