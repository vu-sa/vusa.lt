<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution)->create();
});

describe('user relation', function (): void {
    test('resolves the holder of a User dutiable row', function (): void {
        $user = User::factory()->create();

        $row = Dutiable::factory()->forUser($user)->forDuty($this->duty)->create();

        expect($row->user)->not->toBeNull()
            ->and($row->user->id)->toBe($user->id);

        // Eager loading (DutyController::getDutyUsers) must resolve too.
        $eager = Dutiable::query()->with('user')->whereKey($row->id)->first();
        expect($eager->user)->not->toBeNull()
            ->and($eager->user->id)->toBe($user->id);
    });

    test('does not resolve a row whose morph type is not User', function (): void {
        // Latent today (every row is a User), real the moment a second morph
        // type appears on the pivot — a colliding dutiable_id must not leak an
        // unrelated user through the relation. See issue #623.
        $user = User::factory()->create();

        $row = Dutiable::factory()->forDuty($this->duty)->create([
            'dutiable_type' => MorphMap::alias(Institution::class),
            'dutiable_id' => $user->id,
        ]);

        expect($row->user)->toBeNull();
    });
});
