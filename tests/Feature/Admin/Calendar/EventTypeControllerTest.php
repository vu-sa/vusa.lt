<?php

use App\Models\Calendar;
use App\Models\EventType;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->globalCoordinator = makeTenantUserWithRole('Global Communication Coordinator', $this->tenant);

    $this->eventType = EventType::factory()->create([
        'name' => ['lt' => 'Testinis tipas', 'en' => 'Test type'],
        'slug' => 'testinis-tipas',
    ]);
});

describe('unauthorized access', function (): void {
    test('cannot access index page', function (): void {
        asUser($this->user)
            ->get(route('eventTypes.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function (): void {
        asUser($this->user)
            ->get(route('eventTypes.create'))
            ->assertStatus(403);
    });

    test('cannot store event type', function (): void {
        asUser($this->user)
            ->post(route('eventTypes.store'), [
                'name' => ['lt' => 'Naujas', 'en' => 'New'],
                'slug' => 'naujas',
            ])
            ->assertStatus(403);
    });

    test('cannot access edit page', function (): void {
        asUser($this->user)
            ->get(route('eventTypes.edit', $this->eventType))
            ->assertStatus(403);
    });

    test('cannot update event type', function (): void {
        asUser($this->user)
            ->patch(route('eventTypes.update', $this->eventType), [
                'name' => ['lt' => 'Pakeista', 'en' => 'Changed'],
                'slug' => 'testinis-tipas',
            ])
            ->assertStatus(403);
    });

    test('cannot delete event type', function (): void {
        asUser($this->user)
            ->delete(route('eventTypes.destroy', $this->eventType))
            ->assertStatus(403);
    });
});

describe('authorized access', function (): void {
    test('can access index page', function (): void {
        asUser($this->globalCoordinator)
            ->get(route('eventTypes.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Calendar/IndexEventType')
                ->has('eventTypes')
                ->has('filters')
                ->has('sorting')
            );
    });

    test('can access create page', function (): void {
        asUser($this->globalCoordinator)
            ->get(route('eventTypes.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Calendar/CreateEventType')
            );
    });

    test('can store event type with valid data', function (): void {
        $response = asUser($this->globalCoordinator)->post(route('eventTypes.store'), [
            'name' => ['lt' => 'Diskusija', 'en' => 'Discussion'],
            'slug' => 'diskusija',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $response->assertStatus(302)
            ->assertRedirect(route('eventTypes.index'))
            ->assertSessionHas('success');

        $created = EventType::query()->where('slug', 'diskusija')->first();
        expect($created)->not->toBeNull()
            ->and($created->getTranslation('name', 'lt'))->toBe('Diskusija')
            ->and($created->getTranslation('name', 'en'))->toBe('Discussion');
    });

    test('cannot store event type with invalid data', function (): void {
        asUser($this->globalCoordinator)
            ->post(route('eventTypes.store'), [
                'name' => ['lt' => '', 'en' => ''],
                'slug' => '',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['name.lt', 'name.en', 'slug']);
    });

    test('rejects a duplicate slug', function (): void {
        asUser($this->globalCoordinator)
            ->post(route('eventTypes.store'), [
                'name' => ['lt' => 'Kitas', 'en' => 'Other'],
                'slug' => 'testinis-tipas',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors('slug');
    });

    test('can access edit page', function (): void {
        asUser($this->globalCoordinator)
            ->get(route('eventTypes.edit', $this->eventType))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Calendar/EditEventType')
                ->has('eventType')
                ->where('eventType.id', $this->eventType->id)
            );
    });

    test('can update event type with valid data', function (): void {
        asUser($this->globalCoordinator)
            ->patch(route('eventTypes.update', $this->eventType), [
                'name' => ['lt' => 'Atnaujintas', 'en' => 'Updated'],
                'slug' => 'testinis-tipas',
                'is_active' => false,
                'sort_order' => 9,
            ])
            ->assertStatus(302)
            ->assertRedirect(route('eventTypes.index'))
            ->assertSessionHas('success');

        $updated = $this->eventType->fresh();
        expect($updated->getTranslation('name', 'lt'))->toBe('Atnaujintas')
            ->and($updated->is_active)->toBeFalse()
            ->and($updated->sort_order)->toBe(9);
    });

    test('can delete an event type not in use', function (): void {
        asUser($this->globalCoordinator)
            ->delete(route('eventTypes.destroy', $this->eventType))
            ->assertStatus(302)
            ->assertRedirect(route('eventTypes.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('event_types', ['id' => $this->eventType->id]);
    });

    test('force-delete is refused while a calendar event still references the type', function (): void {
        // No scoped role is seeded with `eventTypes.forceDelete.*` (categories follow the same
        // pattern) — comprehensive coverage genuinely needs the super-admin role here.
        $admin = makeAdminUser($this->tenant);

        Calendar::factory()->for($this->tenant)->create(['event_type_id' => $this->eventType->id]);
        $this->eventType->delete();

        asUser($admin)
            ->delete(route('eventTypes.forceDelete', $this->eventType))
            ->assertStatus(302)
            ->assertSessionHas('error');

        $this->assertSoftDeleted('event_types', ['id' => $this->eventType->id]);
    });
});
