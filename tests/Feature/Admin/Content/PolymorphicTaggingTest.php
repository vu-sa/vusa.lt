<?php

use App\Models\Calendar;
use App\Models\EventType;
use App\Models\News;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(config('permission.super_admin_role_name'));
});

describe('taggables morph pivot', function (): void {
    test('the same tag attaches to news, pages and calendar events', function (): void {
        $tag = Tag::factory()->create();
        $news = News::factory()->create();
        $page = Page::factory()->create();
        $calendar = Calendar::factory()->create();

        $tag->news()->attach($news);
        $tag->pages()->attach($page);
        $tag->calendars()->attach($calendar);

        expect($tag->fresh()->news)->toHaveCount(1)
            ->and($tag->fresh()->pages)->toHaveCount(1)
            ->and($tag->fresh()->calendars)->toHaveCount(1);

        $this->assertDatabaseHas('taggables', ['tag_id' => $tag->id, 'taggable_type' => 'news', 'taggable_id' => $news->id]);
        $this->assertDatabaseHas('taggables', ['tag_id' => $tag->id, 'taggable_type' => 'page', 'taggable_id' => $page->id]);
        $this->assertDatabaseHas('taggables', ['tag_id' => $tag->id, 'taggable_type' => 'calendar', 'taggable_id' => $calendar->id]);
    });

    test('force deleting a page detaches it from every tag', function (): void {
        $tag = Tag::factory()->create();
        $page = Page::factory()->create();
        $tag->pages()->attach($page);

        $page->delete();
        $page->forceDelete();

        $this->assertDatabaseMissing('taggables', ['taggable_type' => 'page', 'taggable_id' => $page->id]);
    });

    test('force deleting a calendar event detaches it from every tag', function (): void {
        $tag = Tag::factory()->create();
        $calendar = Calendar::factory()->create();
        $tag->calendars()->attach($calendar);

        $calendar->delete();
        $calendar->forceDelete();

        $this->assertDatabaseMissing('taggables', ['taggable_type' => 'calendar', 'taggable_id' => $calendar->id]);
    });
});

describe('is_topic flag', function (): void {
    test('scopeTopics returns only tags flagged as topics', function (): void {
        Tag::factory()->create();
        $topic = Tag::factory()->topic()->create();

        $topics = Tag::topics()->get();

        expect($topics)->toHaveCount(1)
            ->and($topics->first()->id)->toBe($topic->id)
            ->and($topic->is_topic)->toBeTrue();
    });
});

describe('page tagging via controller', function (): void {
    test('tags are attached when a page is created', function (): void {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $payload = getControllerTestData('Page')['valid'];
        $payload['tenant_id'] = $this->tenant->id;
        $payload['tags'] = [$tag1->id, $tag2->id];

        asUser($this->superAdmin)
            ->post(route('pages.store'), $payload)
            ->assertRedirect(route('pages.index'))
            ->assertSessionHas('success');

        $page = Page::where('title', $payload['title'])->firstOrFail();

        expect($page->tags)->toHaveCount(2)
            ->and($page->tags->pluck('id')->sort()->values()->all())->toEqual(collect([$tag1->id, $tag2->id])->sort()->values()->all());
    });

    test('tags can be replaced when a page is updated', function (): void {
        $page = Page::factory()->create(['tenant_id' => $this->tenant->id]);
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $page->tags()->attach($tag1);

        $payload = getControllerTestData('Page')['valid'];
        $payload['tags'] = [$tag2->id];

        asUser($this->superAdmin)
            ->patch(route('pages.update', $page), $payload)
            ->assertSessionHas('success');

        expect($page->fresh()->tags->pluck('id')->all())->toEqual([$tag2->id]);
    });
});

describe('calendar tagging via controller', function (): void {
    test('tags are attached when an event is created', function (): void {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $payload = [
            'title' => ['lt' => 'Test įvykis', 'en' => 'Test event'],
            'permalink' => ['lt' => 'test-ivykis'],
            'date' => now()->addWeek()->toISOString(),
            'tenant_id' => $this->tenant->id,
            'event_type_id' => EventType::factory()->create()->id,
            'is_draft' => false,
            'tags' => [$tag1->id, $tag2->id],
        ];

        asUser($this->superAdmin)
            ->post(route('calendar.store'), $payload)
            ->assertRedirect(route('calendar.index'))
            ->assertSessionHas('success');

        $calendar = Calendar::query()->latest('id')->firstOrFail();

        expect($calendar->tags)->toHaveCount(2)
            ->and($calendar->tags->pluck('id')->sort()->values()->all())->toEqual(collect([$tag1->id, $tag2->id])->sort()->values()->all());
    });
});

describe('tag merge', function (): void {
    test('merging tags moves news, page and calendar links to the target', function (): void {
        $source = Tag::factory()->create();
        $target = Tag::factory()->create();
        $news = News::factory()->create();
        $page = Page::factory()->create();
        $calendar = Calendar::factory()->create();

        $source->news()->attach($news);
        $source->pages()->attach($page);
        $source->calendars()->attach($calendar);

        asUser($this->superAdmin)
            ->post(route('tags.processMerge'), [
                'target_tag_id' => $target->id,
                'source_tag_ids' => [$source->id],
            ])
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('success');

        expect($target->fresh()->news)->toHaveCount(1)
            ->and($target->fresh()->pages)->toHaveCount(1)
            ->and($target->fresh()->calendars)->toHaveCount(1);

        $this->assertSoftDeleted('tags', ['id' => $source->id]);
    });
});
