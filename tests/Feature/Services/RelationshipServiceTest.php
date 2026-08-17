<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Models\Vote;
use App\Services\RelationshipService;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    // Create test relationship
    $this->relationship = new Relationship([
        'name' => 'Test Relationship',
        'slug' => 'test-relationship',
        'description' => 'Test relationship description',
    ]);
    $this->relationship->save();

    // Create test institutions
    $this->sourceInstitution = Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Šaltinio institucija', 'en' => 'Source Institution'],
    ]);
    $this->relatedInstitution = Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Susijusi institucija', 'en' => 'Related Institution'],
    ]);
});

describe('getRelatedInstitutionsForMultiple', function (): void {
    test('returns empty collection when no institutions provided', function (): void {
        $result = RelationshipService::getRelatedInstitutionsForMultiple(new Collection);

        expect($result)->toBeInstanceOf(Collection::class)
            ->toBeEmpty();
    });

    test('returns related institutions with direct relationship', function (): void {
        // Create a direct relationship between institutions (without related_model_type)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        // Clear cache to ensure fresh data
        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($this->relatedInstitution->id)
            ->and($result->first()->is_related)->toBeTrue()
            ->and($result->first()->relationship_direction)->toBe('outgoing');
    });

    test('excludes institutions that are in the source collection', function (): void {
        // Create relationship where related institution is also in source
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);
        RelationshipService::clearRelatedInstitutionsCache($this->relatedInstitution->id);

        // Include both institutions in source - related should be excluded
        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution, $this->relatedInstitution])
        );

        expect($result)->toBeEmpty();
    });

    test('eager loads meetings with correct agenda item columns', function (): void {
        // Create a direct relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        // Create a meeting with agenda items for the related institution
        $meeting = Meeting::factory()->create([
            'start_time' => now()->subDays(10),
        ]);
        $meeting->institutions()->attach($this->relatedInstitution->id);

        // Create agenda item and a vote for it (votes are now in separate table)
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Test Agenda Item',
            'type' => 'voting',
        ]);

        Vote::create([
            'agenda_item_id' => $agendaItem->id,
            'is_main' => true,
            'student_vote' => 'positive',
            'decision' => 'positive',
            'student_benefit' => 'positive',
        ]);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        // This should NOT throw "Column not found" error for is_complete
        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );

        expect($result)->toHaveCount(1);

        $relatedInst = $result->first();
        expect($relatedInst->meetings)->toHaveCount(1)
            ->and($relatedInst->meetings->first()->agendaItems)->toHaveCount(1);

        // Verify the agenda item has votes loaded
        $agendaItem = $relatedInst->meetings->first()->agendaItems->first();
        $agendaItem->load('votes');
        expect($agendaItem->votes)->toHaveCount(1)
            ->and($agendaItem->votes->first()->decision)->toBe('positive');
    });

    test('eager loads duties with users for duty member display', function (): void {
        // Create a direct relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        // Create duty with user for the related institution
        $duty = Duty::factory()->for($this->relatedInstitution)->create();
        $user = User::factory()->create();
        $duty->users()->attach($user->id, [
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(2),
        ]);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );

        expect($result)->toHaveCount(1);

        $relatedInst = $result->first();
        expect($relatedInst->duties)->toHaveCount(1)
            ->and($relatedInst->duties->first()->users)->toHaveCount(1)
            ->and($relatedInst->duties->first()->users->first()->id)->toBe($user->id);
    });

    test('includes all meetings without date filter', function (): void {
        // Create a direct relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        // Create old meeting (should now be included)
        $oldMeeting = Meeting::factory()->create([
            'start_time' => now()->subMonths(8),
        ]);
        $oldMeeting->institutions()->attach($this->relatedInstitution->id);

        // Create recent meeting
        $recentMeeting = Meeting::factory()->create([
            'start_time' => now()->subMonths(3),
        ]);
        $recentMeeting->institutions()->attach($this->relatedInstitution->id);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );

        expect($result)->toHaveCount(1);
        // Both meetings should be included (no 6-month filter)
        expect($result->first()->meetings)->toHaveCount(2);
    });

    test('works correctly when source institutions have partial tenant data loaded', function (): void {
        // This test ensures getRelatedInstitutionsForMultiple works correctly when
        // institutions are loaded with partial tenant data (e.g., 'tenant:id,shortname')
        // as done by DutyService::buildInstitutionQuery().
        //
        // Bug scenario: The tenant 'type' column is needed for cross-tenant scope matching,
        // but was not included in the eager load, causing incoming type-based relationships
        // to be incorrectly filtered out.

        // Create tenants with different types
        $padalinysTenant = Tenant::factory()->create(['type' => 'padalinys']);
        $pagrindinisTenant = Tenant::factory()->create(['type' => 'pagrindinis']);

        // Create institutions in different tenants
        $userInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio institucija', 'en' => 'Branch Institution'],
        ]);
        $relatedInstitution = Institution::factory()->for($pagrindinisTenant)->create([
            'name' => ['lt' => 'Pagrindinė institucija', 'en' => 'Main Institution'],
        ]);

        // Create types for type-based relationship
        $userType = Type::factory()->create([
            'title' => ['lt' => 'Vartotojo tipas', 'en' => 'User Type'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);
        $relatedType = Type::factory()->create([
            'title' => ['lt' => 'Susijęs tipas', 'en' => 'Related Type'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);

        // Attach types to institutions
        $userInstitution->types()->attach($userType->id);
        $relatedInstitution->types()->attach($relatedType->id);

        // Create cross-tenant type-based relationship: relatedType -> userType
        // This means userInstitution has INCOMING relationship from relatedInstitution
        $typeRelationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Type::class),
            'relationshipable_id' => $relatedType->id,
            'related_model_id' => $userType->id,
            'scope' => Relationshipable::SCOPE_CROSS_TENANT,
            'bidirectional' => false,
        ]);
        $typeRelationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($userInstitution->id);

        // Load institution with PARTIAL tenant data (simulating DutyService::buildInstitutionQuery)
        $partialLoadInstitution = Institution::select('id', 'name', 'alias', 'tenant_id')
            ->with([
                'tenant:id,shortname', // Missing 'type' column - this was the bug!
                'types',
            ])
            ->find($userInstitution->id);

        // Verify the bug condition: tenant type is empty
        expect($partialLoadInstitution->tenant->type)->toBeEmpty();

        // Call getRelatedInstitutionsForMultiple with the partially-loaded institution
        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$partialLoadInstitution])
        );

        // Should find the related institution (incoming, unauthorized due to bidirectional=false)
        expect($result)->toHaveCount(1, 'Should find incoming type-based relationship despite partial tenant load');
        expect($result->first()->id)->toBe($relatedInstitution->id)
            ->and($result->first()->authorized)->toBeFalse('Incoming non-bidirectional relationship should be unauthorized')
            ->and($result->first()->relationship_direction)->toBe('incoming')
            ->and($result->first()->relationship_type)->toBe('type-based');
    });
});

describe('cache invalidation', function (): void {
    test('cache key is based on institution id', function (): void {
        $cacheKey = RelationshipService::getCacheKey($this->sourceInstitution->id);

        // Ensure cache is clear
        Cache::forget($cacheKey);
        expect(Cache::has($cacheKey))->toBeFalse();

        // Call the cached method
        RelationshipService::getRelatedInstitutionsCached($this->sourceInstitution);

        // Cache should now be populated
        expect(Cache::has($cacheKey))->toBeTrue();
    });

    test('clearRelatedInstitutionsCache clears the cache', function (): void {
        $cacheKey = RelationshipService::getCacheKey($this->sourceInstitution->id);

        // Populate cache
        RelationshipService::getRelatedInstitutionsCached($this->sourceInstitution);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('relationship scope', function (): void {
    beforeEach(function (): void {
        // Create a pagrindinis tenant with unique shortname
        $this->pagrindinisTenant = Tenant::factory()->create([
            'type' => 'pagrindinis',
            'shortname' => 'Test Pagrindinis '.uniqid(),
        ]);

        // Create a padalinys tenant with unique shortname
        $this->padalinysTenant = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Test Padalinys '.uniqid(),
        ]);

        // Create institutions in different tenants
        $this->pagrindineInstitution = Institution::factory()->for($this->pagrindinisTenant)->create([
            'name' => ['lt' => 'VU Senatas', 'en' => 'VU Senate'],
        ]);

        $this->padalinysInstitution = Institution::factory()->for($this->padalinysTenant)->create([
            'name' => ['lt' => 'GMC Taryba', 'en' => 'GMC Council'],
        ]);
    });

    test('within-tenant scope only matches same tenant', function (): void {
        // Create relationship with within-tenant scope (default)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->pagrindineInstitution->id,
            'related_model_id' => $this->padalinysInstitution->id,
            'scope' => Relationshipable::SCOPE_WITHIN_TENANT,
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->pagrindineInstitution->id);

        // Direct relationships should still work regardless of scope
        // because direct relationships don't use tenant filtering
        $result = RelationshipService::getRelatedInstitutionsFlat($this->pagrindineInstitution);

        // Direct relationship should be found
        expect($result)->toHaveCount(1);
    });

    test('scope defaults to within-tenant', function (): void {
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        // Scope should default to within-tenant
        expect($relationshipable->scope)->toBe(Relationshipable::SCOPE_WITHIN_TENANT);
    });

    test('scope constants are defined correctly', function (): void {
        expect(Relationshipable::SCOPE_WITHIN_TENANT)->toBe('within-tenant')
            ->and(Relationshipable::SCOPE_CROSS_TENANT)->toBe('cross-tenant');
    });

    test('cross-tenant scope works when institution has preloaded tenant with partial data', function (): void {
        // This test covers a bug where cross-tenant scope matching failed when
        // the institution was preloaded with partial tenant data (e.g., 'tenant:id,shortname')
        // missing the 'type' column needed for scope matching.

        // Create types for the relationship
        $sourceType = Type::factory()->create([
            'title' => ['lt' => 'KAP Taryba Test', 'en' => 'KAP Council Test'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);
        $targetType = Type::factory()->create([
            'title' => ['lt' => 'Senatas Test', 'en' => 'Senate Test'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);

        // Create cross-tenant type-based relationship: targetType -> sourceType
        // This means institutions with targetType have outgoing relationship to institutions with sourceType
        // And institutions with sourceType have incoming relationship from institutions with targetType
        $typeRelationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Type::class),
            'relationshipable_id' => $targetType->id,
            'related_model_id' => $sourceType->id,
            'scope' => Relationshipable::SCOPE_CROSS_TENANT,
            'bidirectional' => false,
        ]);
        $typeRelationshipable->save();

        // Attach types to institutions
        $this->padalinysInstitution->types()->attach($sourceType->id);
        $this->pagrindineInstitution->types()->attach($targetType->id);

        RelationshipService::clearRelatedInstitutionsCache($this->padalinysInstitution->id);

        // Test 1: With full tenant data - should work
        $fullLoadInstitution = Institution::with(['tenant', 'types'])->find($this->padalinysInstitution->id);
        $resultFull = RelationshipService::getRelatedInstitutionsFlat($fullLoadInstitution);

        $incomingFull = $resultFull->filter(fn ($item) => $item['direction'] === 'incoming' && $item['type'] === 'type-based');
        expect($incomingFull)->toHaveCount(1, 'Full tenant load should find incoming type-based relationship')
            ->and($incomingFull->first()['institution']->id)->toBe($this->pagrindineInstitution->id);

        // Clear cache to test partial load
        RelationshipService::clearRelatedInstitutionsCache($this->padalinysInstitution->id);

        // Test 2: With partial tenant data (missing 'type' column) - should still work
        // This simulates what happens when DutyService::buildInstitutionQuery() loads institutions
        $partialLoadInstitution = Institution::with(['tenant:id,shortname', 'types'])->find($this->padalinysInstitution->id);

        // Verify tenant type is empty (simulating the bug condition)
        expect($partialLoadInstitution->tenant->type)->toBeEmpty('Partial load should have empty tenant type');

        // The fix in institutionMatchesScope should reload tenant when type is missing
        $resultPartial = RelationshipService::getRelatedInstitutionsFlat($partialLoadInstitution);

        $incomingPartial = $resultPartial->filter(fn ($item) => $item['direction'] === 'incoming' && $item['type'] === 'type-based');
        expect($incomingPartial)->toHaveCount(1, 'Partial tenant load should still find incoming type-based relationship')
            ->and($incomingPartial->first()['institution']->id)->toBe($this->pagrindineInstitution->id);
    });
});

describe('type-based cross-tenant authorization', function (): void {
    test('cross-tenant unidirectional authorizes pagrindinis even when incoming', function (): void {
        $pagrindinisTenant = Tenant::factory()->create(['type' => 'pagrindinis']);
        $padalinysTenant = Tenant::factory()->create(['type' => 'padalinys']);

        $pagrindinisInstitution = Institution::factory()->for($pagrindinisTenant)->create([
            'name' => ['lt' => 'Pagrindinė institucija', 'en' => 'Main Institution'],
        ]);
        $padalinysInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio institucija', 'en' => 'Branch Institution'],
        ]);

        $sourceType = Type::factory()->create([
            'title' => ['lt' => 'Šaltinio tipas', 'en' => 'Source Type'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);
        $targetType = Type::factory()->create([
            'title' => ['lt' => 'Tikslo tipas', 'en' => 'Target Type'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);

        $padalinysInstitution->types()->attach($sourceType->id);
        $pagrindinisInstitution->types()->attach($targetType->id);

        $typeRelationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Type::class),
            'relationshipable_id' => $sourceType->id,
            'related_model_id' => $targetType->id,
            'scope' => Relationshipable::SCOPE_CROSS_TENANT,
            'bidirectional' => false,
        ]);
        $typeRelationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($pagrindinisInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($pagrindinisInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item)->toMatchArray(['direction' => 'incoming', 'type' => 'type-based'])
            ->and($item['authorized'])->toBeTrue()
            ->and($item['institution']->id)->toBe($padalinysInstitution->id);
    });

    test('cross-tenant unidirectional does not authorize padalinys even when outgoing', function (): void {
        $pagrindinisTenant = Tenant::factory()->create(['type' => 'pagrindinis']);
        $padalinysTenant = Tenant::factory()->create(['type' => 'padalinys']);

        $pagrindinisInstitution = Institution::factory()->for($pagrindinisTenant)->create([
            'name' => ['lt' => 'Pagrindinė institucija', 'en' => 'Main Institution'],
        ]);
        $padalinysInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio institucija', 'en' => 'Branch Institution'],
        ]);

        $sourceType = Type::factory()->create([
            'title' => ['lt' => 'Šaltinio tipas', 'en' => 'Source Type'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);
        $targetType = Type::factory()->create([
            'title' => ['lt' => 'Tikslo tipas', 'en' => 'Target Type'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);

        $padalinysInstitution->types()->attach($sourceType->id);
        $pagrindinisInstitution->types()->attach($targetType->id);

        $typeRelationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Type::class),
            'relationshipable_id' => $sourceType->id,
            'related_model_id' => $targetType->id,
            'scope' => Relationshipable::SCOPE_CROSS_TENANT,
            'bidirectional' => false,
        ]);
        $typeRelationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($padalinysInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($padalinysInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item)->toMatchArray(['direction' => 'outgoing', 'type' => 'type-based'])
            ->and($item['authorized'])->toBeFalse()
            ->and($item['institution']->id)->toBe($pagrindinisInstitution->id);
    });
});

describe('directional authorization', function (): void {
    test('outgoing relationships have authorized = true', function (): void {
        // Create outgoing relationship (source -> related)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item['direction'])->toBe('outgoing')
            ->and($item['authorized'])->toBeTrue();
    });

    test('incoming relationships have authorized = false', function (): void {
        // Create relationship where this institution is the target
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id, // Other is source
            'related_model_id' => $this->sourceInstitution->id, // This is target
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item['direction'])->toBe('incoming')
            ->and($item['authorized'])->toBeFalse();
    });

    test('getRelatedInstitutions with authorizedOnly = true filters incoming relationships', function (): void {
        // Create bidirectional relationship (both directions)
        // Outgoing: source -> related
        $outgoing = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $outgoing->save();

        // Create third institution
        $thirdInstitution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Trečia institucija', 'en' => 'Third Institution'],
        ]);

        // Incoming: third -> source (source receives but can't see third)
        $incoming = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $thirdInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
        ]);
        $incoming->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        // Without filter - should get both
        $allRelated = RelationshipService::getRelatedInstitutions($this->sourceInstitution, authorizedOnly: false);
        expect($allRelated)->toHaveCount(2);

        // With filter - should only get outgoing (authorized)
        $authorizedOnly = RelationshipService::getRelatedInstitutions($this->sourceInstitution, authorizedOnly: true);
        expect($authorizedOnly)->toHaveCount(1)
            ->and($authorizedOnly->first()->id)->toBe($this->relatedInstitution->id);
    });

    test('getRelatedInstitutionsForMultiple loads meetings only for authorized institutions', function (): void {
        // Create outgoing relationship (authorized)
        $outgoing = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $outgoing->save();

        // Create third institution with incoming relationship (not authorized)
        $thirdInstitution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Trečia institucija', 'en' => 'Third Institution'],
        ]);

        $incoming = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $thirdInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
        ]);
        $incoming->save();

        // Create meetings with agenda items for both related institutions
        $meeting1 = Meeting::factory()->create(['start_time' => now()]);
        $meeting1->institutions()->attach($this->relatedInstitution->id);
        $agendaItem1 = AgendaItem::factory()->create(['meeting_id' => $meeting1->id]);

        $meeting2 = Meeting::factory()->create(['start_time' => now()]);
        $meeting2->institutions()->attach($thirdInstitution->id);
        $agendaItem2 = AgendaItem::factory()->create(['meeting_id' => $meeting2->id]);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );

        expect($result)->toHaveCount(2);

        // Authorized institution should have meetings with agenda items loaded
        $authorizedInst = $result->firstWhere('id', $this->relatedInstitution->id);
        expect($authorizedInst->authorized)->toBeTrue()
            ->and($authorizedInst->relationLoaded('meetings'))->toBeTrue()
            ->and($authorizedInst->meetings)->toHaveCount(1)
            ->and($authorizedInst->meetings->first()->relationLoaded('agendaItems'))->toBeTrue()
            ->and($authorizedInst->meetings->first()->agendaItems)->toHaveCount(1);

        // Unauthorized institution should have meetings but NO agenda items
        $unauthorizedInst = $result->firstWhere('id', $thirdInstitution->id);
        expect($unauthorizedInst->authorized)->toBeFalse()
            ->and($unauthorizedInst->relationLoaded('meetings'))->toBeTrue()
            ->and($unauthorizedInst->meetings)->toHaveCount(1)
            ->and($unauthorizedInst->meetings->first()->relationLoaded('agendaItems'))->toBeFalse();
    });
});

describe('sibling relationships', function (): void {
    test('sibling relationships have authorized = true', function (): void {
        // Create a type with sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'Test Type', 'en' => 'Test Type'],
            'extra_attributes' => ['enable_sibling_relationships' => true],
        ]);

        // Attach both institutions to the same type
        $this->sourceInstitution->types()->attach($type->id);
        $this->relatedInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item)->toMatchArray(['direction' => 'sibling', 'type' => 'within-type'])
            ->and($item['authorized'])->toBeTrue();
    });

    test('sibling relationships only work within same tenant', function (): void {
        // Create a second tenant
        $otherTenant = Tenant::factory()->create([
            'shortname' => 'Other Tenant '.uniqid(),
        ]);

        // Create institution in different tenant
        $otherInstitution = Institution::factory()->for($otherTenant)->create([
            'name' => ['lt' => 'Kita institucija', 'en' => 'Other Institution'],
        ]);

        // Create a type with sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'Test Type', 'en' => 'Test Type'],
            'extra_attributes' => ['enable_sibling_relationships' => true],
        ]);

        // Attach source and other institution (different tenants) to the same type
        $this->sourceInstitution->types()->attach($type->id);
        $otherInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        // Should be empty - sibling relationships require same tenant
        expect($result)->toBeEmpty();
    });

    test('sibling relationships are included in authorization check', function (): void {
        // Create a type with sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'Test Type', 'en' => 'Test Type'],
            'extra_attributes' => ['enable_sibling_relationships' => true],
        ]);

        // Attach both institutions to the same type
        $this->sourceInstitution->types()->attach($type->id);
        $this->relatedInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        // With authorizedOnly = true, sibling relationships should still be included
        $authorizedRelated = RelationshipService::getRelatedInstitutions($this->sourceInstitution, authorizedOnly: true);

        expect($authorizedRelated)->toHaveCount(1)
            ->and($authorizedRelated->first()->id)->toBe($this->relatedInstitution->id);
    });
});

describe('cross-tenant sibling relationships', function (): void {
    test('pagrindinis institution can see padalinys siblings with cross-tenant sibling enabled', function (): void {
        // Create pagrindinis tenant
        $pagrindinissTenant = Tenant::factory()->create([
            'type' => 'pagrindinis',
            'shortname' => 'Pagrindinis Tenant '.uniqid(),
        ]);

        // Create padalinys tenant
        $padalinysTenant = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Padalinys Tenant '.uniqid(),
        ]);

        // Create institutions in each tenant
        $pagrindinisInstitution = Institution::factory()->for($pagrindinissTenant)->create([
            'name' => ['lt' => 'Centrinė AEK', 'en' => 'Central AEK'],
        ]);

        $padalinysInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio AEK', 'en' => 'Faculty AEK'],
        ]);

        // Create a type with cross-tenant sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'AEK Type', 'en' => 'AEK Type'],
            'extra_attributes' => ['enable_cross_tenant_sibling_relationships' => true],
        ]);

        // Attach both institutions to the same type
        $pagrindinisInstitution->types()->attach($type->id);
        $padalinysInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($pagrindinisInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($pagrindinisInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item)->toMatchArray(['direction' => 'sibling', 'type' => 'cross-tenant-sibling'])
            ->and($item['authorized'])->toBeTrue()
            ->and($item['institution']->id)->toBe($padalinysInstitution->id);
    });

    test('padalinys institution can see pagrindinis sibling but without authorization', function (): void {
        // Create pagrindinis tenant
        $pagrindinissTenant = Tenant::factory()->create([
            'type' => 'pagrindinis',
            'shortname' => 'Pagrindinis Tenant '.uniqid(),
        ]);

        // Create padalinys tenant
        $padalinysTenant = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Padalinys Tenant '.uniqid(),
        ]);

        // Create institutions in each tenant
        $pagrindinisInstitution = Institution::factory()->for($pagrindinissTenant)->create([
            'name' => ['lt' => 'Centrinė AEK', 'en' => 'Central AEK'],
        ]);

        $padalinysInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio AEK', 'en' => 'Faculty AEK'],
        ]);

        // Create a type with cross-tenant sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'AEK Type', 'en' => 'AEK Type'],
            'extra_attributes' => ['enable_cross_tenant_sibling_relationships' => true],
        ]);

        // Attach both institutions to the same type
        $pagrindinisInstitution->types()->attach($type->id);
        $padalinysInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($padalinysInstitution->id);

        // Query from padalinys perspective - should see pagrindinis but NOT authorized
        $result = RelationshipService::getRelatedInstitutionsFlat($padalinysInstitution);

        expect($result)->toHaveCount(1);
        $item = $result->first();
        expect($item)->toMatchArray(['direction' => 'sibling', 'type' => 'cross-tenant-sibling'])
            ->and($item['authorized'])->toBeFalse(); // Can see but no data access
        expect($item['institution']->id)->toBe($pagrindinisInstitution->id);
    });

    test('cross-tenant siblings authorization is one-directional', function (): void {
        // Create pagrindinis tenant
        $pagrindinissTenant = Tenant::factory()->create([
            'type' => 'pagrindinis',
            'shortname' => 'Pagrindinis Tenant '.uniqid(),
        ]);

        // Create padalinys tenant
        $padalinysTenant = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Padalinys Tenant '.uniqid(),
        ]);

        // Create institutions in each tenant
        $pagrindinisInstitution = Institution::factory()->for($pagrindinissTenant)->create([
            'name' => ['lt' => 'Centrinė AEK', 'en' => 'Central AEK'],
        ]);

        $padalinysInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio AEK', 'en' => 'Faculty AEK'],
        ]);

        // Create a type with cross-tenant sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'AEK Type', 'en' => 'AEK Type'],
            'extra_attributes' => ['enable_cross_tenant_sibling_relationships' => true],
        ]);

        // Attach both institutions to the same type
        $pagrindinisInstitution->types()->attach($type->id);
        $padalinysInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($pagrindinisInstitution->id);
        RelationshipService::clearRelatedInstitutionsCache($padalinysInstitution->id);

        // Pagrindinis with authorizedOnly = true should see padalinys
        $authorizedFromPagrindinis = RelationshipService::getRelatedInstitutions($pagrindinisInstitution, authorizedOnly: true);
        expect($authorizedFromPagrindinis)->toHaveCount(1)
            ->and($authorizedFromPagrindinis->first()->id)->toBe($padalinysInstitution->id);

        // Padalinys with authorizedOnly = true should NOT see pagrindinis (unauthorized)
        $authorizedFromPadalinys = RelationshipService::getRelatedInstitutions($padalinysInstitution, authorizedOnly: true);
        expect($authorizedFromPadalinys)->toBeEmpty();

        // Padalinys with authorizedOnly = false should see pagrindinis
        $allFromPadalinys = RelationshipService::getRelatedInstitutions($padalinysInstitution, authorizedOnly: false);
        expect($allFromPadalinys)->toHaveCount(1)
            ->and($allFromPadalinys->first()->id)->toBe($pagrindinisInstitution->id);
    });

    test('regular sibling flag does not enable cross-tenant siblings', function (): void {
        // Create pagrindinis tenant
        $pagrindinissTenant = Tenant::factory()->create([
            'type' => 'pagrindinis',
            'shortname' => 'Pagrindinis Tenant '.uniqid(),
        ]);

        // Create padalinys tenant
        $padalinysTenant = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Padalinys Tenant '.uniqid(),
        ]);

        // Create institutions in each tenant
        $pagrindinisInstitution = Institution::factory()->for($pagrindinissTenant)->create([
            'name' => ['lt' => 'Centrinė AEK', 'en' => 'Central AEK'],
        ]);

        $padalinysInstitution = Institution::factory()->for($padalinysTenant)->create([
            'name' => ['lt' => 'Padalinio AEK', 'en' => 'Faculty AEK'],
        ]);

        // Create a type with REGULAR sibling relationships enabled (not cross-tenant)
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'AEK Type', 'en' => 'AEK Type'],
            'extra_attributes' => ['enable_sibling_relationships' => true],
        ]);

        // Attach both institutions to the same type
        $pagrindinisInstitution->types()->attach($type->id);
        $padalinysInstitution->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($pagrindinisInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($pagrindinisInstitution);

        // Should be empty - regular sibling flag only works within same tenant
        expect($result)->toBeEmpty();
    });

    test('pagrindinis can see multiple padalinys siblings', function (): void {
        // Create pagrindinis tenant
        $pagrindinissTenant = Tenant::factory()->create([
            'type' => 'pagrindinis',
            'shortname' => 'Pagrindinis Tenant '.uniqid(),
        ]);

        // Create multiple padalinys tenants
        $padalinysTenant1 = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Padalinys1 '.uniqid(),
        ]);
        $padalinysTenant2 = Tenant::factory()->create([
            'type' => 'padalinys',
            'shortname' => 'Padalinys2 '.uniqid(),
        ]);

        // Create institutions in each tenant
        $pagrindinisInstitution = Institution::factory()->for($pagrindinissTenant)->create([
            'name' => ['lt' => 'Centrinė AEK', 'en' => 'Central AEK'],
        ]);

        $padalinysInstitution1 = Institution::factory()->for($padalinysTenant1)->create([
            'name' => ['lt' => 'Padalinio 1 AEK', 'en' => 'Faculty 1 AEK'],
        ]);

        $padalinysInstitution2 = Institution::factory()->for($padalinysTenant2)->create([
            'name' => ['lt' => 'Padalinio 2 AEK', 'en' => 'Faculty 2 AEK'],
        ]);

        // Create a type with cross-tenant sibling relationships enabled
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'title' => ['lt' => 'AEK Type', 'en' => 'AEK Type'],
            'extra_attributes' => ['enable_cross_tenant_sibling_relationships' => true],
        ]);

        // Attach all institutions to the same type
        $pagrindinisInstitution->types()->attach($type->id);
        $padalinysInstitution1->types()->attach($type->id);
        $padalinysInstitution2->types()->attach($type->id);

        RelationshipService::clearRelatedInstitutionsCache($pagrindinisInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($pagrindinisInstitution);

        expect($result)->toHaveCount(2);

        $institutionIds = $result->pluck('institution.id')->toArray();
        expect($institutionIds)->toContain($padalinysInstitution1->id)
            ->toContain($padalinysInstitution2->id);

        // All should be authorized
        expect($result->every(fn ($item) => $item['authorized'] === true))->toBeTrue();
        expect($result->every(fn ($item) => $item['type'] === 'cross-tenant-sibling'))->toBeTrue();
    });
});

describe('bidirectional relationships', function (): void {
    test('unidirectional incoming relationship has authorized = false', function (): void {
        // Create a one-way relationship: related -> source (source is the target)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
            'bidirectional' => false,
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        expect($result)->toHaveCount(1)
            ->and($result->first()['direction'])->toBe('incoming')
            ->and($result->first()['authorized'])->toBeFalse();
    });

    test('bidirectional incoming relationship has authorized = true', function (): void {
        // Create a bidirectional relationship: related -> source (source is the target, but can see back)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
            'bidirectional' => true,
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        expect($result)->toHaveCount(1)
            ->and($result->first()['direction'])->toBe('incoming')
            ->and($result->first()['authorized'])->toBeTrue();
    });

    test('bidirectional setting is respected in getRelatedInstitutions with authorizedOnly filter', function (): void {
        // Create unidirectional incoming relationship
        $unidirectional = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
            'bidirectional' => false,
        ]);
        $unidirectional->save();

        // Create third institution with bidirectional relationship
        $thirdInstitution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Trečia institucija', 'en' => 'Third Institution'],
        ]);

        $bidirectional = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $thirdInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
            'bidirectional' => true,
        ]);
        $bidirectional->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        // Without filter - should get both
        $allRelated = RelationshipService::getRelatedInstitutions($this->sourceInstitution, authorizedOnly: false);
        expect($allRelated)->toHaveCount(2);

        // With filter - should only get the bidirectional one
        $authorizedOnly = RelationshipService::getRelatedInstitutions($this->sourceInstitution, authorizedOnly: true);
        expect($authorizedOnly)->toHaveCount(1)
            ->and($authorizedOnly->first()->id)->toBe($thirdInstitution->id);
    });

    test('outgoing relationships are always authorized regardless of bidirectional setting', function (): void {
        // Create an outgoing relationship with bidirectional = false (should still be authorized)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
            'bidirectional' => false,
        ]);
        $relationshipable->save();

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsFlat($this->sourceInstitution);

        expect($result)->toHaveCount(1)
            ->and($result->first()['direction'])->toBe('outgoing')
            ->and($result->first()['authorized'])->toBeTrue();
    });

    test('bidirectional relationship loads meetings for incoming authorized institution', function (): void {
        // Create bidirectional incoming relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->relatedInstitution->id,
            'related_model_id' => $this->sourceInstitution->id,
            'bidirectional' => true,
        ]);
        $relationshipable->save();

        // Create meeting for related institution
        $meeting = Meeting::factory()->create(['start_time' => now()]);
        $meeting->institutions()->attach($this->relatedInstitution->id);

        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);

        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );

        expect($result)->toHaveCount(1);
        $inst = $result->first();
        expect($inst->authorized)->toBeTrue()
            ->and($inst->relationLoaded('meetings'))->toBeTrue()
            ->and($inst->meetings)->toHaveCount(1);
    });
});

describe('getAllRelatedInstitutionsEnriched', function (): void {
    test('returns enriched direct edges with relationship metadata', function (): void {
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
            'bidirectional' => true,
        ]);
        $relationshipable->save();

        $edges = RelationshipService::getAllRelatedInstitutionsEnriched();

        $edge = collect($edges)->firstWhere('source', $this->sourceInstitution->id);

        expect($edge)->not->toBeNull()
            ->toMatchArray(['target' => $this->relatedInstitution->id, 'direction' => 'outgoing', 'type' => 'direct'])
            ->and($edge['bidirectional'])->toBeTrue()
            ->and($edge['relationship_name'])->toBe('Test Relationship');
    });

    test('returns a base collection even when there are no direct edges', function (): void {
        // No direct institution edges created; ensures the empty Eloquent collection
        // does not break the merge with array-shaped edges.
        $edges = RelationshipService::getAllRelatedInstitutionsEnriched();

        expect(collect($edges))->toBeInstanceOf(Illuminate\Support\Collection::class);
    });

    test('includes within-type sibling edges flagged as siblings', function (): void {
        $type = Type::factory()->create([
            'model_type' => MorphMap::alias(Institution::class),
            'extra_attributes' => ['enable_sibling_relationships' => true],
        ]);

        $this->sourceInstitution->types()->attach($type->id);
        $this->relatedInstitution->types()->attach($type->id);

        $edges = RelationshipService::getAllRelatedInstitutionsEnriched();

        $sibling = collect($edges)->firstWhere('type', 'within-type');

        expect($sibling)->not->toBeNull()
            ->and($sibling['direction'])->toBe('sibling')
            ->and($sibling['bidirectional'])->toBeFalse();
    });
});

describe('getTypeRelationshipGraph', function (): void {
    test('returns type nodes and type-to-type edges with metadata', function (): void {
        $sourceType = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class), 'title' => ['lt' => 'Tipas A', 'en' => 'Type A']]);
        $targetType = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class), 'title' => ['lt' => 'Tipas B', 'en' => 'Type B']]);

        $this->relationship->description = 'Aprašymas';
        $this->relationship->save();

        $this->sourceInstitution->types()->attach($sourceType->id);
        $this->relatedInstitution->types()->attach($targetType->id);

        new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => MorphMap::alias(Type::class),
            'relationshipable_id' => $sourceType->id,
            'related_model_id' => $targetType->id,
            'scope' => Relationshipable::SCOPE_CROSS_TENANT,
            'bidirectional' => true,
        ])->save();

        $graph = RelationshipService::getTypeRelationshipGraph();

        expect(collect($graph['nodes'])->pluck('id'))->toContain((string) $sourceType->id, (string) $targetType->id);

        $edge = collect($graph['edges'])->firstWhere('source', (string) $sourceType->id);
        expect($edge)->not->toBeNull()
            ->toMatchArray(['target' => (string) $targetType->id, 'scope' => 'cross-tenant'])
            ->and($edge['bidirectional'])->toBeTrue()
            ->and($edge)->toMatchArray(['relationship_name' => 'Test Relationship', 'relationship_description' => 'Aprašymas']);
    });

    test('includes institution types that have no relations', function (): void {
        $isolatedType = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);

        $graph = RelationshipService::getTypeRelationshipGraph();

        expect(collect($graph['nodes'])->pluck('id'))->toContain((string) $isolatedType->id);
    });
});
