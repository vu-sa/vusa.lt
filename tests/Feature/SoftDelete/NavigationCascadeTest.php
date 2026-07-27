<?php

use App\Models\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * `navigation.parent_id` has no foreign key and no cascade, so deleting a parent on
 * its own used to strand its children: neither roots nor children of a surviving root,
 * they vanished from the public menu, the admin list and the trash view alike.
 */
function makeNavItem(int $parentId = 0, string $lang = 'lt'): Navigation
{
    return Navigation::factory()->create([
        'parent_id' => $parentId,
        'lang' => $lang,
        'order' => Navigation::withTrashed()->max('order') + 1,
    ]);
}

test('deleting a parent takes its children with it', function () {
    $parent = makeNavItem();
    $child = makeNavItem($parent->id);

    $parent->delete();

    $this->assertSoftDeleted('navigation', ['id' => $parent->id]);
    $this->assertSoftDeleted('navigation', ['id' => $child->id]);
});

test('the whole subtree goes down, not just the first level', function () {
    $parent = makeNavItem();
    $child = makeNavItem($parent->id);
    $grandchild = makeNavItem($child->id);

    $parent->delete();

    $this->assertSoftDeleted('navigation', ['id' => $grandchild->id]);
});

test('restoring a parent brings back the children that went down with it', function () {
    $parent = makeNavItem();
    $child = makeNavItem($parent->id);
    $grandchild = makeNavItem($child->id);

    $parent->delete();
    $parent->restore();

    $this->assertNotSoftDeleted('navigation', ['id' => $parent->id]);
    $this->assertNotSoftDeleted('navigation', ['id' => $child->id]);
    $this->assertNotSoftDeleted('navigation', ['id' => $grandchild->id]);
});

// Documented trade-off: the branch moves as one unit in both directions. Telling a
// separately-deleted child apart would need a per-deletion marker, since `deleted_at`
// is second-precision and sibling deletions share a timestamp.
test('restoring a parent brings the whole branch back, including an earlier separate deletion', function () {
    $parent = makeNavItem();
    $deletedEarlier = makeNavItem($parent->id);
    $deletedWithParent = makeNavItem($parent->id);

    $deletedEarlier->delete();
    $parent->delete();
    $parent->restore();

    $this->assertNotSoftDeleted('navigation', ['id' => $deletedWithParent->id]);
    $this->assertNotSoftDeleted('navigation', ['id' => $deletedEarlier->id]);
});

test('no item is left unreachable by both the live list and the trash view', function () {
    $parent = makeNavItem();
    $child = makeNavItem($parent->id);

    $parent->delete();

    $live = Navigation::query()->pluck('id');
    $trashed = Navigation::onlyTrashed()->pluck('id');

    expect($live->contains($child->id))->toBeFalse()
        ->and($trashed->contains($child->id))->toBeTrue();
});

test('permanently deleting a parent does not leave orphans behind', function () {
    $parent = makeNavItem();
    $child = makeNavItem($parent->id);

    $parent->delete();
    $parent->forceDelete();

    $this->assertDatabaseMissing('navigation', ['id' => $parent->id]);
    $this->assertDatabaseMissing('navigation', ['id' => $child->id]);
});

test('creating a sibling does not reuse a trashed order slot', function () {
    $first = makeNavItem();
    $first->delete();

    $second = makeNavItem();

    expect($second->order)->toBeGreaterThan($first->order);
});
