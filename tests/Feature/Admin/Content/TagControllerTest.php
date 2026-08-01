<?php

use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);
use App\Models\News;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
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

describe('auth: simple user without permissions', function (): void {
    beforeEach(function (): void {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('cannot index tags', function (): void {
        asUser($this->user)
            ->get(route('tags.index'))
            ->assertStatus(403);
    });

    test('cannot access tag create page', function (): void {
        asUser($this->user)
            ->get(route('tags.create'))
            ->assertStatus(403);
    });

    test('cannot store new tag', function (): void {
        $tagData = [
            'name' => ['lt' => 'Nauja žyma', 'en' => 'New tag'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'alias' => 'new-tag',
        ];

        asUser($this->user)
            ->post(route('tags.store'), $tagData)
            ->assertStatus(403);
    });

    test('cannot edit existing tag', function (): void {
        asUser($this->user)
            ->get(route('tags.edit', $this->tag))
            ->assertStatus(403);
    });

    test('cannot update existing tag', function (): void {
        $updateData = [
            'name' => ['lt' => 'Atnaujinta žyma', 'en' => 'Updated tag'],
            'description' => ['lt' => 'Atnaujintas aprašymas', 'en' => 'Updated description'],
            'alias' => 'updated-tag',
        ];

        asUser($this->user)
            ->patch(route('tags.update', $this->tag), $updateData)
            ->assertStatus(403);
    });

    test('cannot delete tag', function (): void {
        asUser($this->user)
            ->delete(route('tags.destroy', $this->tag))
            ->assertStatus(403);
    });
});

describe('auth: admin user with permissions', function (): void {
    beforeEach(function (): void {
        asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
    });

    test('can index tags', function (): void {
        asUser($this->admin)
            ->get(route('tags.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexTag')
                ->has('tags.data')
                ->has('tags.meta')
            );
    });

    test('can access tag create page', function (): void {
        asUser($this->admin)
            ->get(route('tags.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreateTag')
            );
    });

    test('can store new tag', function (): void {
        $tagData = [
            'name' => ['lt' => 'Nauja žyma', 'en' => 'New tag'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'alias' => 'new-tag',
        ];

        asUser($this->admin)
            ->post(route('tags.store'), $tagData)
            ->assertStatus(302);

        $tag = Tag::where('alias', 'new-tag')->first();
        expect($tag)->not->toBeNull()
            ->and($tag->getTranslations('name'))->toBe(['lt' => 'Nauja žyma', 'en' => 'New tag'])
            ->and($tag->getTranslations('description'))->toBe(['lt' => 'Aprašymas', 'en' => 'Description'])
            ->and($tag->alias)->toBe('new-tag');
    });

    test('can edit existing tag', function (): void {
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

    test('can update existing tag', function (): void {
        $updateData = [
            'name' => ['lt' => 'Atnaujinta žyma', 'en' => 'Updated tag'],
            'description' => ['lt' => 'Atnaujintas aprašymas', 'en' => 'Updated description'],
            'alias' => 'updated-tag',
        ];

        asUser($this->admin)
            ->patch(route('tags.update', $this->tag), $updateData)
            ->assertStatus(302);

        $this->tag->refresh();
        expect($this->tag->getTranslation('name', 'lt'))->toBe('Atnaujinta žyma')
            ->and($this->tag->getTranslation('name', 'en'))->toBe('Updated tag')
            ->and($this->tag->getTranslation('description', 'lt'))->toBe('Atnaujintas aprašymas')
            ->and($this->tag->getTranslation('description', 'en'))->toBe('Updated description')
            ->and($this->tag->alias)->toBe('updated-tag');
    });

    test('can delete tag', function (): void {
        $tagId = $this->tag->id;

        asUser($this->admin)
            ->delete(route('tags.destroy', $this->tag))
            ->assertStatus(302)
            ->assertRedirect(route('tags.index'));

        $this->assertSoftDeleted('tags', ['id' => $tagId]);
    });
});

describe('validation', function (): void {
    test('name is required for both languages', function (): void {
        $invalidData = [
            'name' => ['lt' => '', 'en' => ''],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        ];

        asUser($this->admin)
            ->post(route('tags.store'), $invalidData)
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt', 'name.en']);
    });

    test('alias must be unique', function (): void {
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

    test('can update tag with same alias', function (): void {
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

describe('model functionality', function (): void {
    test('tag factory creates valid tag', function (): void {
        $tag = Tag::factory()->create();

        expect($tag->getTranslations('name'))->toBeArray()
            ->and($tag->getTranslations('description'))->toBeArray()
            ->and($tag->getTranslation('name', 'lt'))->toBeString()
            ->and($tag->getTranslation('name', 'en'))->toBeString();
    });

    test('tag can be associated with news', function (): void {
        $news = News::factory()->create();
        $tag = Tag::factory()->create();

        $news->tags()->attach($tag);

        expect($news->tags)->toHaveCount(1)
            ->and($news->tags->first()->id)->toBe($tag->id)
            ->and($tag->news)->toHaveCount(1)
            ->and($tag->news->first()->id)->toBe($news->id);
    });

    test('tag translations work correctly', function (): void {
        $tag = Tag::factory()->create([
            'name' => ['lt' => 'Lietuviškas pavadinimas', 'en' => 'English name'],
            'description' => ['lt' => 'Lietuviškas aprašymas', 'en' => 'English description'],
        ]);

        expect($tag->getTranslation('name', 'lt'))->toBe('Lietuviškas pavadinimas')
            ->and($tag->getTranslation('name', 'en'))->toBe('English name')
            ->and($tag->getTranslation('description', 'lt'))->toBe('Lietuviškas aprašymas')
            ->and($tag->getTranslation('description', 'en'))->toBe('English description');

        // Test toFullArray returns translation objects
        $fullArray = $tag->toFullArray();
        expect($fullArray)->toMatchArray(['name' => ['lt' => 'Lietuviškas pavadinimas', 'en' => 'English name'], 'description' => ['lt' => 'Lietuviškas aprašymas', 'en' => 'English description']]);
    });
});

describe('tag merging', function (): void {
    beforeEach(function (): void {
        asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
    });

    test('admin can access merge tags page', function (): void {
        asUser($this->admin)
            ->get(route('tags.merge'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/MergeTags')
                ->has('tags')
            );
    });

    test('admin can merge tags successfully', function (): void {
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
        $news1 = News::factory()->create();
        $news2 = News::factory()->create();

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

        // Verify source tags are soft-deleted and hidden from normal queries
        $this->assertSoftDeleted('tags', ['id' => $sourceTag1->id]);
        $this->assertSoftDeleted('tags', ['id' => $sourceTag2->id]);
        expect(Tag::find($sourceTag1->id))->toBeNull()
            ->and(Tag::find($sourceTag2->id))->toBeNull();

        // Verify target tag still exists
        expect(Tag::find($targetTag->id))->not->toBeNull();

        // Verify news are now attached to target tag
        $targetTag->refresh();
        expect($targetTag->news)->toHaveCount(2)
            ->and($targetTag->news->pluck('id')->toArray())->toContain($news1->id, $news2->id);
    });

    test('merging a news already on the target does not create a duplicate', function (): void {
        $targetTag = Tag::factory()->create(['alias' => 'target-dedup']);
        $sourceTag = Tag::factory()->create(['alias' => 'source-dedup']);

        // The same news is attached to BOTH the target and the source.
        $sharedNews = News::factory()->create();
        $targetTag->news()->attach($sharedNews->id);
        $sourceTag->news()->attach($sharedNews->id);

        asUser($this->admin)
            ->post(route('tags.processMerge'), [
                'target_tag_id' => $targetTag->id,
                'source_tag_ids' => [$sourceTag->id],
            ])
            ->assertRedirect(route('tags.index'));

        $targetTag->refresh();
        // No duplicate pivot row for the shared news.
        expect($targetTag->news)->toHaveCount(1);
        expect($targetTag->news->first()->id)->toBe($sharedNews->id);
        $this->assertSoftDeleted('tags', ['id' => $sourceTag->id]);
        expect(Tag::find($sourceTag->id))->toBeNull();
    });

    test('cannot merge tag into itself', function (): void {
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

    test('simple user cannot access merge tags page', function (): void {
        asUser($this->user)
            ->get(route('tags.merge'))
            ->assertStatus(403);
    });

    test('simple user cannot process tag merge', function (): void {
        $mergeData = [
            'target_tag_id' => $this->tag->id,
            'source_tag_ids' => [Tag::factory()->create()->id],
        ];

        asUser($this->user)
            ->post(route('tags.processMerge'), $mergeData)
            ->assertStatus(403);
    });
});
