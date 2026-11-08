<?php

use App\Models\Problem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('problem dates survive editing with the dates received from Inertia', function (string $occurredAt, string $resolvedAt): void {
    $tenant = Tenant::query()->firstOrFail();
    $editor = makeTenantUserWithRole('Problemų redaktorius', $tenant);
    $problem = Problem::factory()->create([
        'tenant_id' => $tenant->id,
        'created_by' => $editor->id,
        'title' => ['lt' => 'Datos patikra', 'en' => 'Date check'],
        'description' => ['lt' => 'Problemos aprašymas', 'en' => 'Problem description'],
        'occurred_at' => $occurredAt,
        'resolved_at' => $resolvedAt,
        'status' => 'resolved',
    ]);

    for ($edit = 0; $edit < 2; $edit++) {
        $response = asUserWithInertia($editor)->get(route('problems.edit', $problem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('problem.occurred_at', $occurredAt)
                ->where('problem.resolved_at', $resolvedAt));

        $received = $response->inertiaProps('problem');

        asUser($editor)->put(route('problems.update', $problem), [
            'title' => $received['title'],
            'description' => $received['description'],
            'tenant_id' => $received['tenant_id'],
            'status' => $received['status'],
            'occurred_at' => $received['occurred_at'],
            'resolved_at' => $received['resolved_at'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($problem->fresh()->occurred_at->toDateString())->toBe($occurredAt);
        expect($problem->fresh()->resolved_at?->toDateString())->toBe($resolvedAt);
    }
})->with([
    'winter offset' => ['2026-03-15', '2026-03-16'],
    'summer offset' => ['2026-07-15', '2026-07-16'],
    'DST boundary' => ['2026-03-29', '2026-03-30'],
]);
