<?php

use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantA = Tenant::query()->first();
    $this->tenantB = Tenant::factory()->create();

    $this->simpleUser = makeUser($this->tenantA);
    $this->resource = Resource::factory()->for($this->tenantB)->create();
});

describe('ResourcePolicy', function (): void {
    test('any authenticated user can view a resource from any tenant', function (): void {
        expect($this->simpleUser->can('view', $this->resource))->toBeTrue();
    });

    test('any authenticated user can view any resource listing', function (): void {
        expect($this->simpleUser->can('viewAny', Resource::class))->toBeTrue();
    });

    test('simple user cannot update a resource in another tenant', function (): void {
        expect($this->simpleUser->can('update', $this->resource))->toBeFalse();
    });

    test('simple user cannot delete a resource in another tenant', function (): void {
        expect($this->simpleUser->can('delete', $this->resource))->toBeFalse();
    });
});
