<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('uses the route type when returning merge candidates', function (): void {
    $tenant = Tenant::query()->first();
    $admin = makeAdminUser($tenant);
    $source = User::factory()->create(['name' => 'Duplicate Member']);
    $candidate = User::factory()->create([
        'name' => 'Candidate Member',
        'email' => 'candidate-merge@example.com',
    ]);

    asUser($admin)
        ->getJson(route('api.v1.admin.mergeCandidates.index', [
            'type' => 'users',
            'source_ids' => [$source->id],
            'query' => 'Candidate',
        ]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', $candidate->id)
        ->assertJsonMissing(['id' => $source->id]);
});

test('returns 422 for an unsupported route type', function (): void {
    $tenant = Tenant::query()->first();
    $admin = makeAdminUser($tenant);

    asUser($admin)
        ->getJson(route('api.v1.admin.mergeCandidates.index', [
            'type' => 'unknown',
            'source_ids' => ['source-id'],
            'query' => 'Candidate',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');
});
