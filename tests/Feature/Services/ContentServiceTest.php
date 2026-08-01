<?php

use App\Models\Content;
use App\Services\ContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new ContentService;
    $this->content = Content::factory()->create();
});

test('creates new content parts in order', function (): void {
    $this->service->updateContentParts($this->content, [
        ['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'options' => null],
        ['type' => 'shadcn-card', 'json_content' => [], 'options' => ['title' => 'A card']],
    ]);

    $parts = $this->content->fresh('parts')->parts;

    expect($parts)->toHaveCount(2)
        ->and($parts[0]->type)->toBe('tiptap')
        ->and($parts[0]->order)->toBe(0)
        ->and($parts[1]->type)->toBe('shadcn-card')
        ->and($parts[1]->order)->toBe(1);
});

test('updates an existing part in place', function (): void {
    $this->content->parts()->create(['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'order' => 0]);
    $existing = $this->content->fresh('parts')->parts->first();

    $this->service->updateContentParts($this->content, [
        ['id' => $existing->id, 'type' => 'tiptap', 'json_content' => ['type' => 'doc', 'updated' => true], 'options' => null],
    ]);

    $updated = $this->content->fresh('parts')->parts->first();
    expect($updated->id)->toBe($existing->id)
        ->and($updated->json_content->toArray())->toMatchArray(['updated' => true]);
});

test('deletes parts that are no longer present in the payload', function (): void {
    $this->content->parts()->create(['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'order' => 0]);
    $this->content->parts()->create(['type' => 'shadcn-card', 'json_content' => [], 'order' => 1]);

    $this->service->updateContentParts($this->content, [
        ['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'options' => null],
    ]);

    expect($this->content->fresh('parts')->parts)->toHaveCount(1);
});

test('rejects an invalid type when creating a part', function (): void {
    Log::spy();

    $this->service->updateContentParts($this->content, [
        ['type' => 'not-a-real-type', 'json_content' => [], 'options' => null],
    ]);

    expect($this->content->fresh('parts')->parts)->toBeEmpty();
    Log::shouldHaveReceived('warning')->once();
});

test('rejects an invalid type when updating an existing part (regression)', function (): void {
    // The enum check used to live only in the "create" branch — an update payload with
    // an invalid type (e.g. a typo, or a stale client sending a removed type) bypassed
    // validation entirely and was written straight to the database.
    $this->content->parts()->create(['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'order' => 0]);
    $existing = $this->content->fresh('parts')->parts->first();
    Log::spy();

    $this->service->updateContentParts($this->content, [
        ['id' => $existing->id, 'type' => 'not-a-real-type', 'json_content' => ['type' => 'doc'], 'options' => null],
    ]);

    $part = $this->content->fresh('parts')->parts->first();
    expect($part->type)->toBe('tiptap'); // unchanged
    Log::shouldHaveReceived('warning')->once();
});

test('round-trips options.width', function (): void {
    $this->service->updateContentParts($this->content, [
        ['type' => 'photo-gallery', 'json_content' => [], 'options' => ['width' => 'wide', 'columns' => '3']],
    ]);

    $part = $this->content->fresh('parts')->parts->first();
    expect($part->options->toArray())->toMatchArray(['width' => 'wide', 'columns' => '3']);
});

test('ignores null entries in the payload', function (): void {
    $this->service->updateContentParts($this->content, [
        null,
        ['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'options' => null],
    ]);

    expect($this->content->fresh('parts')->parts)->toHaveCount(1);
});
