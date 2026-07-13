<?php

use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agenda item whose meeting is soft-deleted still emits the required tenant and institution fields', function () {
    // A soft-deleted meeting makes the `meeting` relation resolve to null. The searchable
    // array must not drop the schema-required fields, otherwise the Typesense import fails
    // with "field declared in the schema, but not found in the document".
    $meeting = Meeting::factory()->create();
    $item = AgendaItem::factory()->create(['meeting_id' => $meeting->id]);

    $meeting->delete();

    // Force the (now soft-deleted) meeting relation to re-resolve — it returns null.
    $item->unsetRelation('meeting');
    $array = $item->toSearchableArray();

    expect($array['meeting_id'])->not->toBeNull()
        ->and($array)->toHaveKey('tenant_ids')
        ->and($array['tenant_ids'])->toBe([])
        ->and($array)->toHaveKey('tenant_shortnames')
        ->and($array['tenant_shortnames'])->toBe([])
        ->and($array)->toHaveKey('institution_ids')
        ->and($array['institution_ids'])->toBe([]);
});
