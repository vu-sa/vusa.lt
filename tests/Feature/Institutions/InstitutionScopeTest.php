<?php

use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\Type;
use App\Services\InstitutionScopeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function scopeResolver(): InstitutionScopeResolver
{
    return app(InstitutionScopeResolver::class);
}

test('a type takes the scope declared on its nearest ancestor', function (): void {
    $root = Type::factory()->forInstitutions(InstitutionScope::University)->create();
    $child = Type::factory()->forInstitutions()->create(['parent_id' => $root->id]);
    $grandchild = Type::factory()->forInstitutions()->create(['parent_id' => $child->id]);

    expect($child->governanceScope())->toBe(InstitutionScope::University)
        ->and($grandchild->governanceScope())->toBe(InstitutionScope::University)
        ->and($child->ownGovernanceScope())->toBeNull();
});

test('a child type declaring its own scope beats its parent', function (): void {
    $root = Type::factory()->forInstitutions(InstitutionScope::University)->create();
    $child = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create(['parent_id' => $root->id]);
    $grandchild = Type::factory()->forInstitutions()->create(['parent_id' => $child->id]);

    expect($child->governanceScope())->toBe(InstitutionScope::Vusa)
        ->and($grandchild->governanceScope())->toBe(InstitutionScope::Vusa);
});

test('a type tree declaring nothing resolves to no scope', function (): void {
    $root = Type::factory()->forInstitutions()->create();
    $child = Type::factory()->forInstitutions()->create(['parent_id' => $root->id]);

    expect(scopeResolver()->forType($child->id))->toBeNull();
});

test('an institution takes the scope of its first typed ancestor', function (): void {
    $root = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create();
    $child = Type::factory()->forInstitutions()->create(['parent_id' => $root->id]);

    $institution = Institution::factory()->create();
    $institution->types()->attach($child->id);

    expect($institution->fresh()->governance_scope)->toBe(InstitutionScope::Vusa);
});

test('an institution with no types falls back to the university scope', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->governance_scope)->toBe(InstitutionScopeResolver::DEFAULT)
        ->and(InstitutionScopeResolver::DEFAULT)->toBe(InstitutionScope::University);
});

test('saving a type invalidates the resolved scope map', function (): void {
    $type = Type::factory()->forInstitutions(InstitutionScope::University)->create();

    expect($type->governanceScope())->toBe(InstitutionScope::University);

    $type->extra_attributes = ['governance_scope' => InstitutionScope::Vusa->value];
    $type->save();

    expect(scopeResolver()->forType($type->id))->toBe(InstitutionScope::Vusa);
});

test('national and international bodies count as external, vusa does not', function (): void {
    expect(InstitutionScope::National->isExternal())->toBeTrue()
        ->and(InstitutionScope::International->isExternal())->toBeTrue()
        ->and(InstitutionScope::University->isExternal())->toBeTrue()
        ->and(InstitutionScope::Vusa->isExternal())->toBeFalse()
        ->and(InstitutionScope::Vusa->isInternal())->toBeTrue();
});
