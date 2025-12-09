<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    
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

describe('getRelatedInstitutionsForMultiple', function () {
    test('returns empty collection when no institutions provided', function () {
        $result = RelationshipService::getRelatedInstitutionsForMultiple(new Collection());
        
        expect($result)->toBeInstanceOf(Collection::class);
        expect($result)->toHaveCount(0);
    });
    
    test('returns related institutions with direct relationship', function () {
        // Create a direct relationship between institutions (without related_model_type)
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => Institution::class,
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();
        
        // Clear cache to ensure fresh data
        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);
        
        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );
        
        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($this->relatedInstitution->id);
        expect($result->first()->is_related)->toBeTrue();
        expect($result->first()->relationship_direction)->toBe('outgoing');
    });
    
    test('excludes institutions that are in the source collection', function () {
        // Create relationship where related institution is also in source
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => Institution::class,
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
        
        expect($result)->toHaveCount(0);
    });
    
    test('eager loads meetings with correct agenda item columns', function () {
        // Create a direct relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => Institution::class,
            'relationshipable_id' => $this->sourceInstitution->id,
            'related_model_id' => $this->relatedInstitution->id,
        ]);
        $relationshipable->save();
        
        // Create a meeting with agenda items for the related institution
        $meeting = Meeting::factory()->create([
            'start_time' => now()->subDays(10),
        ]);
        $meeting->institutions()->attach($this->relatedInstitution->id);
        
        // Create agenda items with the actual columns from the database
        // Using only columns that exist: student_vote, decision, student_benefit
        AgendaItem::factory()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Test Agenda Item',
            'student_vote' => 'už',
            'decision' => 'priimta',
            'student_benefit' => 'teigiamas',
        ]);
        
        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);
        
        // This should NOT throw "Column not found" error for is_complete
        $result = RelationshipService::getRelatedInstitutionsForMultiple(
            new Collection([$this->sourceInstitution])
        );
        
        expect($result)->toHaveCount(1);
        
        $relatedInst = $result->first();
        expect($relatedInst->meetings)->toHaveCount(1);
        expect($relatedInst->meetings->first()->agendaItems)->toHaveCount(1);
        
        // Verify correct columns are loaded
        $agendaItem = $relatedInst->meetings->first()->agendaItems->first();
        expect($agendaItem->student_vote)->toBe('už');
        expect($agendaItem->decision)->toBe('priimta');
        expect($agendaItem->student_benefit)->toBe('teigiamas');
    });
    
    test('eager loads duties with users for duty member display', function () {
        // Create a direct relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => Institution::class,
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
        expect($relatedInst->duties)->toHaveCount(1);
        expect($relatedInst->duties->first()->users)->toHaveCount(1);
        expect($relatedInst->duties->first()->users->first()->id)->toBe($user->id);
    });
    
    test('includes all meetings without date filter', function () {
        // Create a direct relationship
        $relationshipable = new Relationshipable([
            'relationship_id' => $this->relationship->id,
            'relationshipable_type' => Institution::class,
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
});

describe('cache invalidation', function () {
    test('cache key is based on institution id', function () {
        $cacheKey = RelationshipService::getCacheKey($this->sourceInstitution->id);
        
        // Ensure cache is clear
        Cache::forget($cacheKey);
        expect(Cache::has($cacheKey))->toBeFalse();
        
        // Call the cached method
        RelationshipService::getRelatedInstitutionsCached($this->sourceInstitution);
        
        // Cache should now be populated
        expect(Cache::has($cacheKey))->toBeTrue();
    });
    
    test('clearRelatedInstitutionsCache clears the cache', function () {
        $cacheKey = RelationshipService::getCacheKey($this->sourceInstitution->id);
        
        // Populate cache
        RelationshipService::getRelatedInstitutionsCached($this->sourceInstitution);
        expect(Cache::has($cacheKey))->toBeTrue();
        
        // Clear cache
        RelationshipService::clearRelatedInstitutionsCache($this->sourceInstitution->id);
        
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});
