<?php

use App\Models\Calendar;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );
});

function calendarEventUrl(Calendar $calendar): string
{
    return route('calendar.event.2', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $calendar->date->format('Y'),
        'month' => $calendar->date->format('m'),
        'day' => $calendar->date->format('d'),
        'slug' => Str::slug($calendar->title),
    ]);
}

test('the page passes geocoded coordinates for the event location', function (): void {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '54.6871', 'lon' => '25.2797', 'display_name' => 'Vilnius'],
        ]),
    ]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'location' => ['lt' => 'Universiteto g. 3, Vilnius', 'en' => 'Universiteto st. 3, Vilnius'],
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Public/CalendarEvent')
                ->where('eventLocation.lat', 54.6871)
                ->where('eventLocation.lng', 25.2797)
        );
});

test('eventLocation is null when the location cannot be geocoded', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('eventLocation', null));
});

test('the category is eager loaded so the hero can label the event', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $category = Category::factory()->create([
        'name' => ['lt' => 'Konferencija', 'en' => 'Conference'],
    ]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category_id' => $category->id,
        'is_draft' => false,
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('event.category.name', 'Konferencija'));
});

test('the registration URL reaches the page as cto_url', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'cto_url' => ['lt' => 'https://vusa.lt/registracija', 'en' => 'https://vusa.lt/en/registration'],
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('event.cto_url', 'https://vusa.lt/registracija'));
});

test('the hero style reaches the public event page', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'hero_style' => 'split',
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('event.hero_style', 'split'));
});

/**
 * The related list used to read from the whole calendar sorted newest-first, so it
 * surfaced whatever was furthest in the future rather than what is about to happen.
 */
test('the other events are the soonest upcoming ones', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->addDay(),
    ]);

    $soon = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->addDays(3),
    ]);

    $farOff = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->addYear(),
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendar.0.id', $soon->id)
            ->where('calendar.1.id', $farOff->id)
        );
});

test('past events fill the list only when little is coming up', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->addDay(),
    ]);

    $recentlyPast = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->subWeek(),
        'end_date' => null,
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('calendar.0.id', $recentlyPast->id));
});

test('a draft event is never offered alongside another', function (): void {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $event = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => false,
        'date' => now()->addDay(),
    ]);

    Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_draft' => true,
        'date' => now()->addDays(2),
    ]);

    $this->get(calendarEventUrl($event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('calendar', []));
});
