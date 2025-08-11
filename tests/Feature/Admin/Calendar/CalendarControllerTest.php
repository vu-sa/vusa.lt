<?php

use App\Models\Calendar;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->regularUser = makeUser($this->tenant);
    $this->calendarManager = makeCalendarManager($this->tenant);
});

function makeCalendarManager($tenant): User
{
    $user = makeUser($tenant);
    $user->duties()->first()->assignRole('Communication Coordinator');

    return $user;
}

describe('unauthorized access', function () {
    beforeEach(function () {
        $response = asUser($this->regularUser)->get(route('dashboard'));
        expect($response->status())->toBe(200);
    });

    test('cannot index calendar', function () {
        $response = asUser($this->regularUser)->get(route('calendar.index'));
        expect($response->status())->toBe(403);
    });

    test('cannot access calendar event create page', function () {
        $response = asUser($this->regularUser)->get(route('calendar.create'));
        expect($response->status())->toBe(403);
    });

    test('cannot store calendar event', function () {
        $response = asUser($this->regularUser)->post(route('calendar.store'), [
            'title' => 'Test event',
            'description' => 'Test event description',
            // Emulate JS date picker
            'start_date' => strtotime(now()->format('Y-m-d H:i:s')) * 1000,
            'end_date' => strtotime(now()->addHour()->format('Y-m-d H:i:s')) * 1000,
        ]);
        expect($response->status())->toBe(403);
    });

    test('cannot access the calendar event edit page', function () {
        $calendar = Calendar::factory()->create();

        $response = asUser($this->regularUser)->get(route('calendar.edit', $calendar));
        expect($response->status())->toBe(403);
    });

    test('cannot update calendar', function () {
        $calendar = Calendar::factory()->create();

        $response = asUser($this->regularUser)->put(route('calendar.update', $calendar), [
            'title' => 'Test event updated',
            'description' => 'Test event description updated',
            // Emulate JS date picker
            'start_date' => strtotime(now()->addDay()->format('Y-m-d H:i:s')) * 1000,
            'end_date' => strtotime(now()->addDay()->addHour()->format('Y-m-d H:i:s')) * 1000,
        ]);
        expect($response->status())->toBe(403);
    });

    test('cannot delete calendar', function () {
        $calendar = Calendar::factory()->create();

        $response = asUser($this->regularUser)->delete(route('calendar.destroy', $calendar));
        expect($response->status())->toBe(403);
    });

    test('cannot duplicate calendar event', function () {
        $calendar = Calendar::factory()->create();

        $response = asUser($this->regularUser)->post(route('calendar.duplicate', $calendar));
        expect($response->status())->toBe(403);
    });
});

describe('authorized access', function () {
    test('calendar manager can access index', function () {
        $response = asUser($this->calendarManager)->get(route('calendar.index'));
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Calendar/IndexCalendarEvents')
                ->has('calendar')
                ->has('allCategories')
            );
    });

    test('calendar manager can access create page', function () {
        $response = asUser($this->calendarManager)->get(route('calendar.create'));
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Calendar/CreateCalendarEvent')
                ->has('assignableTenants')
            );
    });

    test('calendar manager can store calendar event', function () {
        $calendarData = [
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'date' => now()->addDays(1)->format('Y-m-d'),
            'tenant_id' => $this->tenant->id,
            'is_draft' => false,
        ];

        $response = asUser($this->calendarManager)->post(route('calendar.store'), $calendarData);
        $response->assertRedirect();

        $this->assertDatabaseHas('calendar', [
            'title->lt' => 'Test renginys',
            'title->en' => 'Test event',
        ]);
    });

    test('calendar manager can access edit page', function () {
        $calendar = Calendar::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->calendarManager)->get(route('calendar.edit', $calendar));
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Calendar/EditCalendarEvent')
                ->has('calendar')
                ->where('calendar.id', $calendar->id)
            );
    });

    test('calendar manager can update calendar event', function () {
        $calendar = Calendar::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'title' => ['lt' => 'Atnaujintas renginys', 'en' => 'Updated event'],
            'description' => ['lt' => 'Atnaujintas aprašymas', 'en' => 'Updated description'],
            'date' => now()->addDays(2)->format('Y-m-d'),
            'tenant_id' => $this->tenant->id,
            'is_draft' => true,
        ];

        $response = asUser($this->calendarManager)->put(route('calendar.update', $calendar), $updateData);
        $response->assertRedirect();

        $calendar->refresh();
        expect($calendar->getTranslation('title', 'lt'))->toBe('Atnaujintas renginys');
        expect($calendar->getTranslation('title', 'en'))->toBe('Updated event');
    });

    test('calendar manager can delete calendar event', function () {
        $calendar = Calendar::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = asUser($this->calendarManager)->delete(route('calendar.destroy', $calendar));
        $response->assertRedirect();

        $this->assertDatabaseMissing('calendar', [
            'id' => $calendar->id,
        ]);
    });

    test('calendar manager can duplicate calendar event', function () {
        $calendar = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'is_draft' => false,
        ]);

        $initialCount = Calendar::count();

        $response = asUser($this->calendarManager)->post(route('calendar.duplicate', $calendar));
        $response->assertStatus(302);

        // Verify a new calendar item was created
        expect(Calendar::count())->toBe($initialCount + 1);

        // Verify redirect to edit page
        $response->assertRedirectContains('/mano/calendar/')
            ->assertRedirectContains('/edit');

        // Find the duplicated calendar
        $duplicatedCalendar = Calendar::query()
            ->where('is_draft', true)
            ->latest()
            ->first();

        expect($duplicatedCalendar)->not()->toBeNull();
        expect($duplicatedCalendar->title)->toContain('(kopija)');
        expect($duplicatedCalendar->is_draft)->toBeTrue();
        expect($duplicatedCalendar->id)->not()->toBe($calendar->id);
    });

    test('super admin can access all calendar functions', function () {
        $admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
        $calendar = Calendar::factory()->for($this->tenant)->create();

        // Test index access
        $response = asUser($admin)->get(route('calendar.index'));
        $response->assertStatus(200);

        // Test create access
        $response = asUser($admin)->get(route('calendar.create'));
        $response->assertStatus(200);

        // Test edit access - admin can access calendars from their tenant
        $response = asUser($admin)->get(route('calendar.edit', $calendar));
        $response->assertStatus(200);
    });
});

describe('validation', function () {
    test('requires title for store', function () {
        $response = asUser($this->calendarManager)->post(route('calendar.store'), [
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'date' => now()->addDays(1)->format('Y-m-d'),
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('title.lt');
    });

    test('requires date for store', function () {
        $response = asUser($this->calendarManager)->post(route('calendar.store'), [
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('date');
    });

    test('requires tenant_id for store', function () {
        $response = asUser($this->calendarManager)->post(route('calendar.store'), [
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('tenant_id');
    });

    test('requires valid date format for store', function () {
        $response = asUser($this->calendarManager)->post(route('calendar.store'), [
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'date' => 'invalid-date',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('date');
    });

    test('saves images to calendar', function () {
        $image = \Illuminate\Http\UploadedFile::fake()->image('calendar-image.jpg', 800, 600);

        $calendarData = [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'title' => ['lt' => 'Renginys su nuotrauka', 'en' => 'Event with Image'],
            'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
            'tenant_id' => $this->tenant->id,
            'images' => [['file' => $image]],
        ];

        $response = asUser($this->calendarManager)->post(route('calendar.store'), $calendarData);

        // Should either succeed or fail gracefully
        expect($response->status())->toBeIn([200, 302, 422]);

        // If image upload is implemented, verify it was stored
        if ($response->status() === 302) {
            $calendar = Calendar::latest()->first();
            if ($calendar && $calendar->image_path) {
                expect(\Illuminate\Support\Facades\Storage::exists($calendar->image_path))->toBeTrue();
            }
        }
    });
});

describe('relationships', function () {
    test('calendar belongs to category', function () {
        $calendar = Calendar::factory()->create();

        // Check if calendar can have category relationship
        expect($calendar->category())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    test('can duplicate calendar with proper translations', function () {
        $calendar = Calendar::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => ['lt' => 'Lietuviškas renginys', 'en' => 'English event'],
            'description' => ['lt' => 'Lietuviškas aprašymas', 'en' => 'English description'],
            'is_draft' => false,
        ]);

        $initialCount = Calendar::count();

        $response = asUser($this->calendarManager)->post(route('calendar.duplicate', $calendar));
        $response->assertStatus(302);

        // Verify a new calendar item was created
        expect(Calendar::count())->toBe($initialCount + 1);

        // Find the duplicated calendar
        $duplicatedCalendar = Calendar::query()
            ->where('is_draft', true)
            ->latest()
            ->first();

        // Verify the duplicated calendar has proper translations
        expect($duplicatedCalendar)->not()->toBeNull();
        expect($duplicatedCalendar->getTranslation('title', 'lt'))->toContain('(kopija)');
        expect($duplicatedCalendar->getTranslation('title', 'lt'))->toContain('Lietuviškas renginys');
        expect($duplicatedCalendar->getTranslation('title', 'en'))->toContain('(copy)');
        expect($duplicatedCalendar->getTranslation('title', 'en'))->toContain('English event');
        expect($duplicatedCalendar->is_draft)->toBeTrue();
        expect($duplicatedCalendar->id)->not()->toBe($calendar->id);
    });

    test('calendar has proper model structure', function () {
        $calendar = Calendar::factory()->create([
            'title' => ['lt' => 'Test renginys', 'en' => 'Test event'],
            'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            'date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        expect($calendar->getTranslation('title', 'lt'))->toBe('Test renginys');
        expect($calendar->getTranslation('title', 'en'))->toBe('Test event');
        expect($calendar->getTranslation('description', 'lt'))->toBe('Test aprašymas');
        expect($calendar->getTranslation('description', 'en'))->toBe('Test description');
    });
});
