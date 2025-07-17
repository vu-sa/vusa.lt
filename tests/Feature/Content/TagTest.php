<?php

use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use Inertia\Testing\AssertableInertia as Assert;


beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTagAdmin($this->tenant);

    $this->tag = Tag::factory()->create([
        'name' => ['lt' => 'Testas', 'en' => 'Test'],
        'description' => ['lt' => 'Testo aprašymas', 'en' => 'Test description'],
        'alias' => 'test-alias',
    ]);
});

function makeTagAdmin($tenant): User
{
    $user = makeUser($tenant);
    $user->duties()->first()->assignRole('Global Communication Coordinator');

    return $user;
}

describe('auth: simple user without permissions', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('cannot index tags', function () {
        asUser($this->user)
            ->get(route('tags.index'))
            ->assertStatus(302)
            ->assertRedirectToRoute('dashboard');
    });

    test('cannot access tag create page', function () {
        asUser($this->user)
            ->get(route('tags.create'))
            ->assertStatus(302);
    });

    test('cannot store new tag', function () {
        $tagData = [
            'name' => ['lt' => 'Nauja žyma', 'en' => 'New tag'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'alias' => 'new-tag',
        ];

        asUser($this->user)
            ->post(route('tags.store'), $tagData)
            ->assertStatus(302);
    });

    test('cannot edit existing tag', function () {
        asUser($this->user)
            ->get(route('tags.edit', $this->tag))
            ->assertStatus(302);
    });

    test('cannot update existing tag', function () {
        $updateData = [
            'name' => ['lt' => 'Atnaujinta žyma', 'en' => 'Updated tag'],
            'description' => ['lt' => 'Atnaujintas aprašymas', 'en' => 'Updated description'],
            'alias' => 'updated-tag',
        ];

        asUser($this->user)
            ->patch(route('tags.update', $this->tag), $updateData)
            ->assertStatus(302);
    });

    test('cannot delete tag', function () {
        asUser($this->user)
            ->delete(route('tags.destroy', $this->tag))
            ->assertStatus(302);
    });
});

describe('auth: admin user with permissions', function () {
    beforeEach(function () {
        asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
    });

    test('can index tags', function () {
        asUser($this->admin)
            ->get(route('tags.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexTag')
                ->has('tags.data')
                ->has('tags.meta')
            );
    });

    test('can access tag create page', function () {
        asUser($this->admin)
            ->get(route('tags.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreateTag')
            );
    });

    test('can store new tag', function () {
        $tagData = [
            'name' => ['lt' => 'Nauja žyma', 'en' => 'New tag'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'alias' => 'new-tag',
        ];

        asUser($this->admin)
            ->post(route('tags.store'), $tagData)
            ->assertStatus(302);

        $tag = Tag::where('alias', 'new-tag')->first();
        expect($tag)->not->toBeNull();
        expect($tag->getTranslations('name'))->toBe(['lt' => 'Nauja žyma', 'en' => 'New tag']);
        expect($tag->getTranslations('description'))->toBe(['lt' => 'Aprašymas', 'en' => 'Description']);
        expect($tag->alias)->toBe('new-tag');
    });

    test('can edit existing tag', function () {
        asUser($this->admin)
            ->get(route('tags.edit', $this->tag))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/EditTag')
                ->has('postTag.name.lt')
                ->has('postTag.name.en')
                ->where('postTag.id', $this->tag->id)
            );
    });

    test('can update existing tag', function () {
        $updateData = [
            'name' => ['lt' => 'Atnaujinta žyma', 'en' => 'Updated tag'],
            'description' => ['lt' => 'Atnaujintas aprašymas', 'en' => 'Updated description'],
            'alias' => 'updated-tag',
        ];

        asUser($this->admin)
            ->patch(route('tags.update', $this->tag), $updateData)
            ->assertStatus(302);

        $this->tag->refresh();
        expect($this->tag->getTranslation('name', 'lt'))->toBe('Atnaujinta žyma');
        expect($this->tag->getTranslation('name', 'en'))->toBe('Updated tag');
        expect($this->tag->getTranslation('description', 'lt'))->toBe('Atnaujintas aprašymas');
        expect($this->tag->getTranslation('description', 'en'))->toBe('Updated description');
        expect($this->tag->alias)->toBe('updated-tag');
    });

    test('can delete tag', function () {
        $tagId = $this->tag->id;

        asUser($this->admin)
            ->delete(route('tags.destroy', $this->tag))
            ->assertStatus(302)
            ->assertRedirect(route('tags.index'));

        expect(Tag::find($tagId))->toBeNull();
    });
});

describe('validation', function () {
    test('name is required for both languages', function () {
        $invalidData = [
            'name' => ['lt' => '', 'en' => ''],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        ];

        asUser($this->admin)
            ->post(route('tags.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt', 'name.en']);
    });

    test('alias must be unique', function () {
        $existingTag = Tag::factory()->create(['alias' => 'unique-alias']);

        $invalidData = [
            'name' => ['lt' => 'Nauja žyma', 'en' => 'New tag'],
            'alias' => 'unique-alias',
        ];

        asUser($this->admin)
            ->post(route('tags.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['alias']);
    });

    test('can update tag with same alias', function () {
        $updateData = [
            'name' => ['lt' => 'Atnaujinta žyma', 'en' => 'Updated tag'],
            'alias' => $this->tag->alias, // Same alias
        ];

        asUser($this->admin)
            ->patch(route('tags.update', $this->tag), $updateData)
            ->assertStatus(302)
            ->assertSessionDoesntHaveErrors();
    });
});

describe('model functionality', function () {
    test('tag factory creates valid tag', function () {
        $tag = Tag::factory()->create();

        expect($tag->getTranslations('name'))->toBeArray();
        expect($tag->getTranslations('description'))->toBeArray();
        expect($tag->getTranslation('name', 'lt'))->toBeString();
        expect($tag->getTranslation('name', 'en'))->toBeString();
    });

    test('tag can be associated with news', function () {
        $news = \App\Models\News::factory()->create();
        $tag = Tag::factory()->create();

        $news->tags()->attach($tag);

        expect($news->tags)->toHaveCount(1);
        expect($news->tags->first()->id)->toBe($tag->id);
        expect($tag->news)->toHaveCount(1);
        expect($tag->news->first()->id)->toBe($news->id);
    });

    test('tag translations work correctly', function () {
        $tag = Tag::factory()->create([
            'name' => ['lt' => 'Lietuviškas pavadinimas', 'en' => 'English name'],
            'description' => ['lt' => 'Lietuviškas aprašymas', 'en' => 'English description'],
        ]);

        expect($tag->getTranslation('name', 'lt'))->toBe('Lietuviškas pavadinimas');
        expect($tag->getTranslation('name', 'en'))->toBe('English name');
        expect($tag->getTranslation('description', 'lt'))->toBe('Lietuviškas aprašymas');
        expect($tag->getTranslation('description', 'en'))->toBe('English description');

        // Test toFullArray returns translation objects
        $fullArray = $tag->toFullArray();
        expect($fullArray['name'])->toBe(['lt' => 'Lietuviškas pavadinimas', 'en' => 'English name']);
        expect($fullArray['description'])->toBe(['lt' => 'Lietuviškas aprašymas', 'en' => 'English description']);
    });
});

describe('tag merging', function () {
    beforeEach(function () {
        asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
    });

    test('admin can access merge tags page', function () {
        asUser($this->admin)
            ->get(route('tags.merge'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/MergeTags')
                ->has('tags')
            );
    });

    test('admin can merge tags successfully', function () {
        // Create additional tags
        $targetTag = Tag::factory()->create([
            'name' => ['lt' => 'Tikslinė žyma', 'en' => 'Target tag'],
            'alias' => 'target-tag',
        ]);

        $sourceTag1 = Tag::factory()->create([
            'name' => ['lt' => 'Šaltinio žyma 1', 'en' => 'Source tag 1'],
            'alias' => 'source-tag-1',
        ]);

        $sourceTag2 = Tag::factory()->create([
            'name' => ['lt' => 'Šaltinio žyma 2', 'en' => 'Source tag 2'],
            'alias' => 'source-tag-2',
        ]);

        // Create news and attach source tags
        $news1 = \App\Models\News::factory()->create();
        $news2 = \App\Models\News::factory()->create();

        $sourceTag1->news()->attach($news1->id);
        $sourceTag2->news()->attach($news2->id);

        $mergeData = [
            'target_tag_id' => $targetTag->id,
            'source_tag_ids' => [$sourceTag1->id, $sourceTag2->id],
        ];

        asUser($this->admin)
            ->post(route('tags.processMerge'), $mergeData)
            ->assertRedirect(route('tags.index'))
            ->assertSessionHas('success');

        // Verify source tags are deleted
        expect(Tag::find($sourceTag1->id))->toBeNull();
        expect(Tag::find($sourceTag2->id))->toBeNull();

        // Verify target tag still exists
        expect(Tag::find($targetTag->id))->not->toBeNull();

        // Verify news are now attached to target tag
        $targetTag->refresh();
        expect($targetTag->news)->toHaveCount(2);
        expect($targetTag->news->pluck('id')->toArray())->toContain($news1->id, $news2->id);
    });

    test('cannot merge tag into itself', function () {
        $tag = Tag::factory()->create();

        $mergeData = [
            'target_tag_id' => $tag->id,
            'source_tag_ids' => [$tag->id],
        ];

        asUser($this->admin)
            ->post(route('tags.processMerge'), $mergeData)
            ->assertRedirect()
            ->assertSessionHasErrors(['source_tag_ids']);
    });

    test('simple user cannot access merge tags page', function () {
        asUser($this->user)
            ->get(route('tags.merge'))
            ->assertStatus(302);
    });

    test('simple user cannot process tag merge', function () {
        $mergeData = [
            'target_tag_id' => $this->tag->id,
            'source_tag_ids' => [Tag::factory()->create()->id],
        ];

        asUser($this->user)
            ->post(route('tags.processMerge'), $mergeData)
            ->assertStatus(302);
    });
});
