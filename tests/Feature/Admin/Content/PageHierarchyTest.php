<?php

use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

describe('page parent validation', function (): void {
    test('rejects a page as its own parent', function (): void {
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $page->title,
            'parent_id' => $page->id,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $page), $payload)
            ->assertSessionHasErrors('parent_id');

        expect($page->refresh()->parent_id)->toBeNull();
    });

    test('rejects a parent from a different language', function (): void {
        $enPage = Page::factory()->for($this->tenant)->create(['lang' => 'en']);
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $page->title,
            'parent_id' => $enPage->id,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $page), $payload)
            ->assertSessionHasErrors('parent_id');
    });

    test('rejects a parent from a different tenant', function (): void {
        $otherPage = Page::factory()->for($this->otherTenant)->create(['lang' => 'lt']);
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $page->title,
            'parent_id' => $otherPage->id,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $page), $payload)
            ->assertSessionHasErrors('parent_id');
    });

    test('rejects a cycle where the candidate parent is a descendant', function (): void {
        $grandparent = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
        $child = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'parent_id' => $grandparent->id]);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $grandparent->title,
            'parent_id' => $child->id,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $grandparent), $payload)
            ->assertSessionHasErrors('parent_id');
    });

    test('rejects nesting past 3 levels', function (): void {
        $root = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
        $middle = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'parent_id' => $root->id]);
        $leaf = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'parent_id' => $middle->id]);
        $tooDeep = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $tooDeep->title,
            'parent_id' => $leaf->id,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $tooDeep), $payload)
            ->assertSessionHasErrors('parent_id');
    });

    test('accepts a same-lang, same-tenant parent within depth', function (): void {
        $parent = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $page->title,
            'parent_id' => $parent->id,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $page), $payload)
            ->assertSessionHasNoErrors();

        expect($page->refresh()->parent_id)->toBe($parent->id);
    });

    test('clearing the parent is allowed', function (): void {
        $parent = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'parent_id' => $parent->id]);

        $payload = array_merge(getControllerTestData('Page')['valid'], [
            'title' => $page->title,
            'parent_id' => null,
        ]);

        asUser($this->admin)
            ->patch(route('pages.update', $page), $payload)
            ->assertSessionHasNoErrors();

        expect($page->refresh()->parent_id)->toBeNull();
    });
});

describe('Page::descendantIds and ancestors', function (): void {
    test('descendantIds returns the full subtree, not just direct children', function (): void {
        $root = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
        $child = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'parent_id' => $root->id]);
        $grandchild = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'parent_id' => $child->id]);
        Page::factory()->for($this->tenant)->create(['lang' => 'lt']); // unrelated page

        expect($root->descendantIds())->toEqualCanonicalizing([$child->id, $grandchild->id]);
    });

    test('ancestors returns the chain from root to immediate parent', function (): void {
        $root = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'title' => 'Root']);
        $middle = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'title' => 'Middle', 'parent_id' => $root->id]);
        $leaf = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'title' => 'Leaf', 'parent_id' => $middle->id]);

        expect(array_map(fn (Page $p) => $p->title, $leaf->ancestors()))->toBe(['Root', 'Middle']);
    });
});
