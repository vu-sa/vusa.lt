<?php

use App\Models\News;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    
    // Create a user with Communication Coordinator role for news management
    $this->newsManager = User::factory()->create();
    $communicationCoordinatorDuty = \App\Models\Duty::factory()->has(\App\Models\Institution::factory()->state(
        ['tenant_id' => $this->tenant->id]
    ))->hasAttached($this->newsManager, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])->create();
    $communicationCoordinatorDuty->assignRole('Communication Coordinator');
});

describe('News Tag Functionality', function () {
    
    test('news can have tags attached', function () {
        $news = News::factory()->create();
        $tag1 = Tag::factory()->create(['name' => ['lt' => 'Testas 1', 'en' => 'Test 1']]);
        $tag2 = Tag::factory()->create(['name' => ['lt' => 'Testas 2', 'en' => 'Test 2']]);
        
        $news->tags()->sync([$tag1->id, $tag2->id]);
        
        expect($news->tags)->toHaveCount(2);
        expect($news->tags->pluck('id')->toArray())->toEqual([$tag1->id, $tag2->id]);
    });
    
    test('tags can be updated on news', function () {
        $news = News::factory()->create();
        $tag1 = Tag::factory()->create(['name' => ['lt' => 'Testas 1', 'en' => 'Test 1']]);
        $tag2 = Tag::factory()->create(['name' => ['lt' => 'Testas 2', 'en' => 'Test 2']]);
        $tag3 = Tag::factory()->create(['name' => ['lt' => 'Testas 3', 'en' => 'Test 3']]);
        
        // Initial tags
        $news->tags()->sync([$tag1->id, $tag2->id]);
        expect($news->tags)->toHaveCount(2);
        
        // Update tags
        $news->tags()->sync([$tag2->id, $tag3->id]);
        $news->refresh(); // Refresh to get updated relationships
        expect($news->tags)->toHaveCount(2);
        expect($news->tags->pluck('id')->sort()->values()->toArray())->toEqual(collect([$tag2->id, $tag3->id])->sort()->values()->toArray());
    });
    
    test('all tags can be removed from news', function () {
        $news = News::factory()->create();
        $tag1 = Tag::factory()->create(['name' => ['lt' => 'Testas 1', 'en' => 'Test 1']]);
        
        $news->tags()->sync([$tag1->id]);
        expect($news->tags)->toHaveCount(1);
        
        $news->tags()->sync([]);
        $news->refresh(); // Refresh to get updated relationships
        expect($news->tags)->toHaveCount(0);
    });
    
    test('news can be created with tags via controller', function () {
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
                    ]
                ]
            ],
            'lang' => 'lt',
            'image' => 'test-image.jpg',
            'publish_time' => now()->toISOString(),
            'short' => 'Short description',
            'tags' => [$tag1->id, $tag2->id],
            'tenant_id' => $this->tenant->id, // Ensure news is created for correct tenant
        ];
        
        // Use super admin to avoid permission issues for this test
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));
        
        asUser($superAdmin)
            ->post(route('news.store'), $newsData)
            ->assertRedirect(route('news.index'))
            ->assertSessionHas('success');
        
        $news = News::where('title', 'Test News')->first();
        expect($news)->not->toBeNull();
        expect($news->tags)->toHaveCount(2);
        expect($news->tags->pluck('id')->toArray())->toEqual([$tag1->id, $tag2->id]);
    });
    
    test('news tags can be updated via controller', function () {
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
                    ]
                ]
            ],
            'tags' => [$tag2->id, $tag3->id], // Update tags
        ];
        
        // Use super admin to avoid permission issues for this test
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(config('permission.super_admin_role_name'));
        
        $response = asUser($superAdmin)
            ->patch(route('news.update', $news), $updateData)
            ->assertSessionHas('success');
        
        $news->refresh();
        expect($news->tags)->toHaveCount(2);
        expect($news->tags->pluck('id')->sort()->values()->toArray())->toEqual(collect([$tag2->id, $tag3->id])->sort()->values()->toArray());
    });
});
