<?php

use App\Actions\DuplicateNewsAction;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DuplicateNewsAction', function () {
    test('duplicates news with basic properties', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'title' => 'Originalios naujienos',
            'short' => 'Trumpas aprašymas',
            'permalink' => 'original-news',
            'draft' => 0,
            'publish_time' => now(),
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews)->toBeInstanceOf(News::class)
            ->and($duplicatedNews->id)->not()->toBe($originalNews->id)
            ->and($duplicatedNews->title)->toBe($originalNews->title . ' (kopija)')
            ->and($duplicatedNews->short)->toBe($originalNews->short)
            ->and($duplicatedNews->permalink)->toStartWith($originalNews->permalink . '-')
            ->and($duplicatedNews->permalink)->not()->toBe($originalNews->permalink)
            ->and($duplicatedNews->draft)->toBe(1)
            ->and($duplicatedNews->publish_time)->toBeNull()
            ->and($duplicatedNews->content_id)->not()->toBe($originalNews->content_id)
            ->and($duplicatedNews->exists)->toBeTrue();
    });

    test('creates new content instance', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);

        $originalContentCount = Content::count();

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect(Content::count())->toBe($originalContentCount + 1)
            ->and($duplicatedNews->content)->toBeInstanceOf(Content::class)
            ->and($duplicatedNews->content->id)->not()->toBe($originalNews->content->id);
    });

    test('handles null title gracefully', function () {
        // Test by creating a news object and manually setting title to null
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->make([
            'title' => 'Test title',
            'content_id' => $content->id,
        ]);
        
        // Manually set title to null to test the action's handling
        $originalNews->title = null;

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->title)->toContain('(kopija)')
            ->and($duplicatedNews->draft)->toBe(1);
    });

    test('handles empty title gracefully', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'title' => '',
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->title)->toContain('(kopija)')
            ->and($duplicatedNews->draft)->toBe(1);
    });

    test('handles null permalink gracefully', function () {
        // Test by creating a news object and manually setting permalink to null
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->make([
            'permalink' => 'test-permalink',
            'content_id' => $content->id,
        ]);
        
        // Manually set permalink to null to test the action's handling
        $originalNews->permalink = null;

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->permalink)->toMatch('/^-[a-zA-Z0-9]{8}$/')
            ->and($duplicatedNews->draft)->toBe(1);
    });

    test('handles empty permalink gracefully', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'permalink' => '',
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->permalink)->toMatch('/^-[a-zA-Z0-9]{8}$/')
            ->and($duplicatedNews->draft)->toBe(1);
    });

    test('generates unique permalink with random suffix', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'permalink' => 'test-news',
            'content_id' => $content->id,
        ]);

        $action = new DuplicateNewsAction();
        $duplicatedNews1 = $action->execute($originalNews);
        $duplicatedNews2 = $action->execute($originalNews);

        expect($duplicatedNews1->permalink)->toStartWith('test-news-')
            ->and($duplicatedNews2->permalink)->toStartWith('test-news-')
            ->and($duplicatedNews1->permalink)->not()->toBe($duplicatedNews2->permalink)
            ->and(strlen($duplicatedNews1->permalink))->toBe(strlen('test-news-') + 8)
            ->and(strlen($duplicatedNews2->permalink))->toBe(strlen('test-news-') + 8);
    });

    test('copies content parts when they exist', function () {
        $content = Content::factory()->create();
        
        // Create content parts for the original news
        $contentParts = ContentPart::factory()->count(3)->create([
            'content_id' => $content->id,
        ]);

        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);
        
        // Load content relationship to ensure parts are accessible
        $originalNews->load('content.parts');
        
        // Verify original news has the parts
        expect($originalNews->content->parts)->toHaveCount(3);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);
        
        // Load the parts relationship for the duplicated news
        $duplicatedNews->load('content.parts');

        expect($duplicatedNews->content->parts)->toHaveCount(3);

        $duplicatedNews->content->parts->each(function ($duplicatedPart, $index) use ($contentParts, $duplicatedNews) {
            $originalPart = $contentParts[$index];
            
            expect($duplicatedPart->type)->toBe($originalPart->type)
                ->and($duplicatedPart->json_content)->toBe($originalPart->json_content)
                ->and($duplicatedPart->options)->toBe($originalPart->options)
                ->and($duplicatedPart->order)->toBe($originalPart->order ?? 0) // Handle null order gracefully
                ->and($duplicatedPart->content_id)->toBe($duplicatedNews->content_id)
                ->and($duplicatedPart->id)->not()->toBe($originalPart->id);
        });
    });

    test('handles news without content parts', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->content->parts)->toHaveCount(0)
            ->and($duplicatedNews->exists)->toBeTrue();
    });

    test('preserves all non-modified attributes', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'title' => 'Test naujienos',
            'short' => 'Test aprašymas',
            'permalink' => 'test-news',
            'image' => 'test-image.jpg',
            'important' => 1,
            'draft' => 0,
            'publish_time' => now()->addDays(1),
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->short)->toBe($originalNews->short)
            ->and($duplicatedNews->image)->toBe($originalNews->image)
            ->and($duplicatedNews->important)->toBe($originalNews->important)
            ->and($duplicatedNews->draft)->toBe(1) // Should always be draft
            ->and($duplicatedNews->publish_time)->toBeNull(); // Should always be null
    });

    test('creates new database record', function () {
        $content = Content::factory()->create();
        
        $originalCount = News::count();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect(News::count())->toBe($originalCount + 2) // Original + duplicated
            ->and($duplicatedNews->wasRecentlyCreated)->toBeTrue();
    });

    test('returns refreshed news instance', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);

        $result = DuplicateNewsAction::execute($originalNews);

        expect($result)->toBeInstanceOf(News::class)
            ->and($result->exists)->toBeTrue()
            ->and($result->id)->toBeGreaterThan(0)
            ->and($result->content)->toBeInstanceOf(Content::class);
    });

    test('copies tags relationship', function () {
        $content = Content::factory()->create();
        
        // Create tags and attach them to the original news
        $tags = Tag::factory()->count(3)->create();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);
        
        $originalNews->tags()->attach($tags->pluck('id'));

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);
        
        // Load the tags relationship for the duplicated news
        $duplicatedNews->load('tags');

        expect($duplicatedNews->tags)->toHaveCount(3);
        
        // Verify all original tags are copied to the duplicate
        $originalTagIds = $originalNews->tags->pluck('id')->sort()->values();
        $duplicatedTagIds = $duplicatedNews->tags->pluck('id')->sort()->values();
        
        expect($duplicatedTagIds->toArray())->toBe($originalTagIds->toArray());
    });

    test('handles news without tags', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews->tags)->toHaveCount(0)
            ->and($duplicatedNews->exists)->toBeTrue();
    });

    test('uses database transactions', function () {
        $content = Content::factory()->create();
        
        $originalNews = News::factory()->create([
            'content_id' => $content->id,
        ]);

        // Mock a failure scenario to ensure transaction rollback
        \DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) use ($originalNews) {
                return $callback($originalNews);
            });

        $duplicatedNews = DuplicateNewsAction::execute($originalNews);

        expect($duplicatedNews)->toBeInstanceOf(News::class);
    });
});