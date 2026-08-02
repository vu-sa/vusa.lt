<?php

use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->resourceManager = makeUser($this->tenant);
    $this->resourceManager->duties()->first()->assignRole('Resource Manager');

    $this->resource = Resource::factory()->for($this->tenant)->create([
        'identifier' => 'OLD-CODE',
        'is_reservable' => false,
    ]);
});

describe('update', function (): void {
    test('persists the identifier field', function (): void {
        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'identifier' => 'NEW-CODE',
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
        ])->assertRedirect();

        expect($this->resource->fresh()->identifier)->toBe('NEW-CODE');
    });

    test('persists is_reservable as a boolean', function (): void {
        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
        ])->assertRedirect();

        expect($this->resource->fresh()->is_reservable)->toBeTrue();
    });

    test('retains existing media and adds newly uploaded files', function (): void {
        $this->resource->addMedia(UploadedFile::fake()->image('existing.jpg'))->toMediaCollection('images');
        $existingMedia = $this->resource->fresh()->getMedia('images')->sole();

        $newImage = UploadedFile::fake()->image('new.jpg');

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
            'media' => [
                ['id' => $existingMedia->id, 'status' => 'finished'],
                ['file' => $newImage, 'status' => 'pending'],
            ],
        ])->assertRedirect();

        $mediaNames = $this->resource->fresh()->getMedia('images')->pluck('name')->all();
        expect($mediaNames)->toHaveCount(2)
            ->and($mediaNames)->toContain($existingMedia->name);
    });

    test('accepts webp uploads (the format the browser-side compressor produces)', function (): void {
        $webpImage = UploadedFile::fake()->image('good-rep.webp', 100, 100);

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
            'media' => [
                ['file' => $webpImage, 'status' => 'pending'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($this->resource->fresh()->getMedia('images'))->toHaveCount(1);
    });

    test('deletes media omitted from the submitted media array', function (): void {
        $this->resource->addMedia(UploadedFile::fake()->image('to-be-removed.jpg'))->toMediaCollection('images');

        asUser($this->resourceManager)->patch(route('resources.update', $this->resource), [
            'name' => $this->resource->getTranslations('name'),
            'description' => $this->resource->getTranslations('description'),
            'location' => $this->resource->location,
            'tenant_id' => $this->tenant->id,
            'capacity' => $this->resource->capacity,
            'is_reservable' => true,
            'media' => [],
        ])->assertRedirect();

        expect($this->resource->fresh()->getMedia('images'))->toHaveCount(0);
    });
});

describe('destroy', function (): void {
    test('redirects straight to the search results, not through resources.index', function (): void {
        // resources.index itself immediately redirects to search.index. Redirecting
        // there first (rather than straight to search.index) ages the flash message
        // out one hop before Inertia ever renders a page, so the toast never fires.
        asUser($this->resourceManager)->delete(route('resources.destroy', $this->resource))
            ->assertRedirect(route('search.index', ['tab' => 'resources']))
            ->assertSessionHas('info');
    });

    test('the flash message survives to the page the browser actually renders', function (): void {
        asUser($this->resourceManager)->delete(route('resources.destroy', $this->resource))
            ->assertSessionHas('info');

        $expectedMessage = trans_choice('messages.deleted', 1, ['model' => trans_choice('entities.resource.model', 1)]);

        asUser($this->resourceManager)->get(route('search.index', ['tab' => 'resources']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Search/SearchIndex')
                ->where('flash.info', $expectedMessage));
    });
});

describe('store', function (): void {
    test('redirects straight to the search results with a success flash', function (): void {
        $expectedMessage = trans_choice('messages.created', 1, ['model' => trans_choice('entities.resource.model', 1)]);

        asUser($this->resourceManager)->post(route('resources.store'), [
            'name' => ['lt' => 'Naujas', 'en' => 'New'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'location' => 'Room 1',
            'tenant_id' => $this->tenant->id,
            'capacity' => 1,
            'is_reservable' => true,
            'media' => [],
        ])
            ->assertRedirect(route('search.index', ['tab' => 'resources']))
            ->assertSessionHas('success');

        asUser($this->resourceManager)->get(route('search.index', ['tab' => 'resources']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Search/SearchIndex')
                ->where('flash.success', $expectedMessage));
    });
});
