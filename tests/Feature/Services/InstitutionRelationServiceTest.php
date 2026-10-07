<?php

use App\Enums\InstitutionRelationKind;
use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\InstitutionRelationService;
use App\Services\Typesense\TypesenseScopedKeyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function relations(): InstitutionRelationService
{
    return app(InstitutionRelationService::class);
}

/** @return array<string, mixed>|null */
function relationOf(Institution $viewer, Institution $other): ?array
{
    return relations()->relatedTo($viewer->id)->firstWhere('institution_id', $other->id);
}

beforeEach(function (): void {
    [$this->tenant, $this->otherTenant] = Tenant::query()->where('type', TenantType::Padalinys)->orderBy('id')->take(2)->get()->all();
    $this->mainTenant = Tenant::main();

    $this->source = Institution::factory()->for($this->tenant)->create();
    $this->target = Institution::factory()->for($this->tenant)->create();
});

describe('direct links', function (): void {
    test('the source sees the target, the target only lists the source', function (): void {
        InstitutionLink::factory()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id, 'kind' => InstitutionRelationKind::Oversees]);

        expect(relationOf($this->source, $this->target))->toMatchArray(['direction' => 'outgoing', 'via' => 'direct', 'kind' => 'oversees', 'authorized' => true])
            ->and(relationOf($this->target, $this->source))->toMatchArray(['direction' => 'incoming', 'authorized' => false]);
    });

    test('a mutual link authorizes both sides', function (): void {
        InstitutionLink::factory()->mutual()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id]);

        expect(relationOf($this->source, $this->target))->toMatchArray(['direction' => 'mutual', 'authorized' => true])
            ->and(relationOf($this->target, $this->source))->toMatchArray(['direction' => 'mutual', 'authorized' => true]);
    });

    test('a trashed institution drops out of the graph', function (): void {
        InstitutionLink::factory()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id]);

        $this->target->delete();

        expect(relations()->relatedTo($this->source->id))->toBeEmpty();
    });
});

describe('type links', function (): void {
    beforeEach(function (): void {
        $this->sourceType = InstitutionType::factory()->create();
        $this->targetType = InstitutionType::factory()->create();
        $this->source->types()->attach($this->sourceType);
        $this->target->types()->attach($this->targetType);
    });

    test('relate institutions of the two types within one tenant only', function (): void {
        $elsewhere = Institution::factory()->for($this->otherTenant)->create();
        $elsewhere->types()->attach($this->targetType);

        InstitutionTypeLink::factory()->create(['source_type_id' => $this->sourceType->id, 'target_type_id' => $this->targetType->id]);

        expect(relationOf($this->source, $this->target))->toMatchArray(['direction' => 'outgoing', 'via' => 'type', 'authorized' => true])
            ->and(relationOf($this->target, $this->source))->toMatchArray(['direction' => 'incoming', 'authorized' => false])
            ->and(relationOf($this->source, $elsewhere))->toBeNull();
    });

    test('cross-tenant links run from pagrindinis sources to padalinys targets', function (): void {
        $central = Institution::factory()->for($this->mainTenant)->create();
        $central->types()->attach($this->sourceType);
        $centralTarget = Institution::factory()->for($this->mainTenant)->create();
        $centralTarget->types()->attach($this->targetType);

        InstitutionTypeLink::factory()->crossTenant()->create(['source_type_id' => $this->sourceType->id, 'target_type_id' => $this->targetType->id]);

        expect(relationOf($central, $this->target))->toMatchArray(['authorized' => true, 'cross_tenant' => true])
            ->and(relationOf($this->target, $central))->toMatchArray(['authorized' => false])
            // A padalinys source and a same-tenant target are outside a cross-tenant link.
            ->and(relationOf($this->source, $centralTarget))->toBeNull()
            ->and(relationOf($this->source, $this->target))->toBeNull();
    });

    test('a mutual self-link relates same-type institutions within each tenant', function (): void {
        $peer = Institution::factory()->for($this->tenant)->create();
        $peer->types()->attach($this->sourceType);
        $elsewhere = Institution::factory()->for($this->otherTenant)->create();
        $elsewhere->types()->attach($this->sourceType);

        InstitutionTypeLink::factory()->mutual()->create(['source_type_id' => $this->sourceType->id, 'target_type_id' => $this->sourceType->id]);

        expect(relationOf($this->source, $peer))->toMatchArray(['direction' => 'mutual', 'authorized' => true])
            ->and(relationOf($peer, $this->source))->toMatchArray(['direction' => 'mutual', 'authorized' => true])
            ->and(relationOf($this->source, $elsewhere))->toBeNull()
            ->and(relationOf($this->source, $this->source))->toBeNull();
    });

    test('a one-way cross-tenant self-link lets pagrindinis see padaliniai, not the reverse', function (): void {
        $central = Institution::factory()->for($this->mainTenant)->create();
        $central->types()->attach($this->sourceType);
        $faculty = Institution::factory()->for($this->otherTenant)->create();
        $faculty->types()->attach($this->sourceType);

        InstitutionTypeLink::factory()->crossTenant()->create(['source_type_id' => $this->sourceType->id, 'target_type_id' => $this->sourceType->id]);

        expect(relations()->authorizedIdsFor([$central->id])->sort()->values()->all())->toBe(collect([$this->source->id, $faculty->id])->sort()->values()->all())
            ->and(relationOf($faculty, $central))->toMatchArray(['authorized' => false])
            ->and(relationOf($this->source, $faculty))->toBeNull();
    });

    test('the strongest relation wins when several reach the same pair', function (): void {
        // The type link only lists the source to the target; the direct link authorizes it.
        InstitutionTypeLink::factory()->create(['source_type_id' => $this->targetType->id, 'target_type_id' => $this->sourceType->id]);
        InstitutionLink::factory()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id]);

        expect(relationOf($this->source, $this->target))->toMatchArray(['via' => 'direct', 'authorized' => true])
            ->and(relations()->relatedTo($this->source->id))->toHaveCount(1);
    });
});

test('the institution graph is the same edge set access is computed from', function (): void {
    $type = InstitutionType::factory()->create();
    $peer = Institution::factory()->for($this->tenant)->create();
    $this->source->types()->attach($type);
    $peer->types()->attach($type);
    InstitutionTypeLink::factory()->mutual()->create(['source_type_id' => $type->id, 'target_type_id' => $type->id]);
    InstitutionLink::factory()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id]);

    $edges = relations()->institutionGraph();

    expect($edges)->toHaveCount(2);
    foreach ($edges as $edge) {
        expect(relations()->authorizedIdsFor([$edge['source']]))->toContain($edge['target']);
    }
});

test('the type graph lists every type and the links between them', function (): void {
    $linked = InstitutionTypeLink::factory()->crossTenant()->create();
    $unlinked = InstitutionType::factory()->create();

    $graph = relations()->typeGraph();

    expect(collect($graph['nodes'])->pluck('id'))->toContain((string) $unlinked->id, (string) $linked->source_type_id)
        ->and($graph['edges'])->toHaveCount(1)
        ->and($graph['edges'][0])->toMatchArray(['source' => (string) $linked->source_type_id, 'cross_tenant' => true, 'kind' => 'related']);
});

describe('forMultiple', function (): void {
    test('skips the given institutions and loads agenda items only where authorized', function (): void {
        $incoming = Institution::factory()->for($this->tenant)->create();
        InstitutionLink::factory()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id]);
        InstitutionLink::factory()->create(['source_institution_id' => $incoming->id, 'target_institution_id' => $this->source->id]);

        foreach ([$this->target, $incoming] as $institution) {
            $meeting = Meeting::factory()->create();
            $meeting->institutions()->attach($institution);
            AgendaItem::factory()->create(['meeting_id' => $meeting->id]);
        }

        $result = relations()->forMultiple(new Collection([$this->source]))->keyBy('id');

        expect($result)->not->toHaveKey($this->source->id)
            ->and($result[$this->target->id]->authorized)->toBeTrue()
            ->and($result[$this->target->id]->relationship_direction)->toBe('outgoing')
            ->and($result[$this->target->id]->source_institution_id)->toBe($this->source->id)
            ->and($result[$this->target->id]->meetings->first()->relationLoaded('agendaItems'))->toBeTrue()
            ->and($result[$incoming->id]->authorized)->toBeFalse()
            ->and($result[$incoming->id]->meetings->first()->relationLoaded('agendaItems'))->toBeFalse();
    });

    test('returns an empty collection when nothing is related', function (): void {
        expect(relations()->forMultiple(new Collection([$this->source])))->toBeEmpty();
    });
});

describe('invalidation', function (): void {
    test('link changes refresh warmed results without manual cache clearing', function (string $kind): void {
        if ($kind === 'type') {
            $sourceType = InstitutionType::factory()->create();
            $targetType = InstitutionType::factory()->create();
            $this->source->types()->attach($sourceType);
            $this->target->types()->attach($targetType);
        }

        expect(relations()->relatedTo($this->source->id))->toBeEmpty();

        $link = $kind === 'type'
            ? InstitutionTypeLink::factory()->create(['source_type_id' => $sourceType->id, 'target_type_id' => $targetType->id])
            : InstitutionLink::factory()->create(['source_institution_id' => $this->source->id, 'target_institution_id' => $this->target->id]);

        expect(relationOf($this->target, $this->source)['authorized'])->toBeFalse();

        $link->update(['mutual' => true]);
        expect(relationOf($this->target, $this->source)['authorized'])->toBeTrue();

        $link->delete();
        expect(relations()->relatedTo($this->source->id))->toBeEmpty();
    })->with(['direct', 'type']);

    test('type assignments and tenant moves refresh warmed results', function (): void {
        $type = InstitutionType::factory()->create();
        $this->source->types()->attach($type);
        InstitutionTypeLink::factory()->mutual()->create(['source_type_id' => $type->id, 'target_type_id' => $type->id]);

        expect(relationOf($this->source, $this->target))->toBeNull();

        $this->target->types()->attach($type);
        expect(relationOf($this->source, $this->target))->not->toBeNull();

        $this->target->update(['tenant_id' => $this->otherTenant->id]);
        expect(relationOf($this->source, $this->target))->toBeNull();
    });

    test('a type-link change retires affected members\' access and search key caches', function (): void {
        $sourceType = InstitutionType::factory()->create();
        $targetType = InstitutionType::factory()->create();
        $this->source->types()->attach($sourceType);
        $this->target->types()->attach($targetType);

        $member = User::factory()->create();
        Duty::factory()->for($this->source)->create()->users()->attach($member, ['start_date' => now()->subDay()]);

        $access = app(InstitutionAccessService::class);
        expect($access->getAccessibleInstitutionIds($member))->not->toContain($this->target->id);
        $keyVersion = TypesenseScopedKeyService::visibilityVersion();

        InstitutionTypeLink::factory()->create(['source_type_id' => $sourceType->id, 'target_type_id' => $targetType->id]);

        expect($access->getAccessibleInstitutionIds($member))->toContain($this->target->id)
            ->and(TypesenseScopedKeyService::visibilityVersion())->not->toBe($keyVersion);
    });
});
