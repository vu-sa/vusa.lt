<?php

use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

function relationshipsManager(Tenant $tenant): User
{
    $user = makeUser($tenant);
    $user->duties()->first()->givePermissionTo(['relationships.create.*', 'relationships.update.*', 'relationships.delete.*']);

    return $user;
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->other = Institution::factory()->for($this->tenant)->create();
    $this->type = InstitutionType::factory()->create();
    $this->manager = relationshipsManager($this->tenant);
    // Editing an institution never extends to linking it: links grant data access.
    $this->coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
});

describe('unauthorized access', function (): void {
    test('a tenant-scoped coordinator cannot link institutions or types', function (): void {
        asUser($this->coordinator)
            ->post(route('institutions.links.store', $this->institution), ['other_institution_id' => $this->other->id, 'direction' => 'outgoing', 'kind' => 'related'])
            ->assertForbidden();
        asUser($this->coordinator)
            ->post(route('institutionTypes.links.store', $this->type), ['other_type_id' => $this->type->id, 'direction' => 'outgoing', 'kind' => 'related'])
            ->assertForbidden();

        expect(InstitutionLink::query()->exists())->toBeFalse()
            ->and(InstitutionTypeLink::query()->exists())->toBeFalse();
    });

    test('cannot change or remove links', function (): void {
        $link = InstitutionLink::factory()->create(['source_institution_id' => $this->institution->id, 'target_institution_id' => $this->other->id]);
        $typeLink = InstitutionTypeLink::factory()->create();

        asUser($this->coordinator)->patch(route('institutionLinks.update', $link), ['kind' => 'oversees', 'mutual' => true])->assertForbidden();
        asUser($this->coordinator)->delete(route('institutionLinks.destroy', $link))->assertForbidden();
        asUser($this->coordinator)->delete(route('institutionTypeLinks.destroy', $typeLink))->assertForbidden();

        expect($link->fresh()->mutual)->toBeFalse()
            ->and($typeLink->fresh())->not->toBeNull();
    });
});

describe('institution links', function (): void {
    test('links the record to another institution in the chosen direction', function (): void {
        asUser($this->manager)
            ->post(route('institutions.links.store', $this->institution), ['other_institution_id' => $this->other->id, 'direction' => 'incoming', 'kind' => 'oversees', 'mutual' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(InstitutionLink::query()->sole())
            ->source_institution_id->toBe($this->other->id)
            ->target_institution_id->toBe($this->institution->id)
            ->kind->value->toBe('oversees')
            ->mutual->toBeTrue();
    });

    test('rejects a self-link, an unknown kind and a pair already linked either way', function (): void {
        asUser($this->manager)
            ->post(route('institutions.links.store', $this->institution), ['other_institution_id' => $this->institution->id, 'direction' => 'outgoing', 'kind' => 'friends'])
            ->assertSessionHasErrors(['other_institution_id', 'kind']);

        InstitutionLink::factory()->create(['source_institution_id' => $this->other->id, 'target_institution_id' => $this->institution->id]);

        asUser($this->manager)
            ->post(route('institutions.links.store', $this->institution), ['other_institution_id' => $this->other->id, 'direction' => 'outgoing', 'kind' => 'related'])
            ->assertSessionHasErrors('other_institution_id');

        expect(InstitutionLink::query()->count())->toBe(1);
    });

    test('updates only the kind and mutuality, and deletes', function (): void {
        $link = InstitutionLink::factory()->create(['source_institution_id' => $this->institution->id, 'target_institution_id' => $this->other->id]);

        asUser($this->manager)
            ->patch(route('institutionLinks.update', $link), ['kind' => 'cooperates', 'mutual' => true, 'target_institution_id' => $this->institution->id])
            ->assertRedirect();

        expect($link->fresh())
            ->kind->value->toBe('cooperates')
            ->mutual->toBeTrue()
            ->target_institution_id->toBe($this->other->id);

        asUser($this->manager)->delete(route('institutionLinks.destroy', $link))->assertRedirect();

        expect($link->fresh())->toBeNull();
    });
});

describe('institution type links', function (): void {
    test('links a type to itself across tenants', function (): void {
        asUser($this->manager)
            ->post(route('institutionTypes.links.store', $this->type), ['other_type_id' => $this->type->id, 'direction' => 'outgoing', 'kind' => 'related', 'cross_tenant' => true])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(InstitutionTypeLink::query()->sole())
            ->source_type_id->toBe($this->type->id)
            ->target_type_id->toBe($this->type->id)
            ->cross_tenant->toBeTrue()
            ->mutual->toBeFalse();
    });

    test('rejects a duplicate and a trashed type', function (): void {
        $other = InstitutionType::factory()->create();
        InstitutionTypeLink::factory()->create(['source_type_id' => $other->id, 'target_type_id' => $this->type->id]);

        asUser($this->manager)
            ->post(route('institutionTypes.links.store', $this->type), ['other_type_id' => $other->id, 'direction' => 'incoming', 'kind' => 'related'])
            ->assertSessionHasErrors('other_type_id');

        $other->delete();

        asUser($this->manager)
            ->post(route('institutionTypes.links.store', $this->type), ['other_type_id' => $other->id, 'direction' => 'outgoing', 'kind' => 'related'])
            ->assertSessionHasErrors('other_type_id');

        expect(InstitutionTypeLink::query()->count())->toBe(1);
    });

    test('updates and deletes', function (): void {
        $link = InstitutionTypeLink::factory()->create(['source_type_id' => $this->type->id]);

        asUser($this->manager)->patch(route('institutionTypeLinks.update', $link), ['kind' => 'advisory', 'mutual' => true])->assertRedirect();

        expect($link->fresh())->kind->value->toBe('advisory')->mutual->toBeTrue();

        asUser($this->manager)->delete(route('institutionTypeLinks.destroy', $link))->assertRedirect();

        expect($link->fresh())->toBeNull();
    });
});
