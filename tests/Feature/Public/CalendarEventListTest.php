<?php

use App\Models\Calendar;
use App\Models\EventType;
use App\Models\Tenant;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('calendar search matches each translated field only in the page language', function (string $field, string $locale): void {
    $tenant = Tenant::main();
    $attributes = [
        'title' => ['lt' => 'Mokymai', 'en' => 'Talk'],
        'description' => ['lt' => 'Tema', 'en' => 'Topic'],
        'location' => ['lt' => 'Sala', 'en' => 'Hall'],
        'is_draft' => false,
        'is_international' => true,
        'date' => now()->addDay(),
    ];
    $matching = Calendar::factory()->for($tenant)->create([
        ...$attributes,
        $field => ['lt' => $locale === 'lt' ? 'Stipendija' : 'Tema', 'en' => $locale === 'en' ? 'Scholarship' : 'Topic'],
    ]);
    Calendar::factory()->for($tenant)->create([
        ...$attributes,
        $field => ['lt' => $locale === 'en' ? 'Scholarship' : 'Tema', 'en' => $locale === 'lt' ? 'Stipendija' : 'Topic'],
    ]);

    $this->get(LocalizedRouteSlugs::route('calendar.list', [
        'search' => $locale === 'lt' ? 'stipendija' : 'SCHOLARSHIP',
    ], $locale))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Public/CalendarEventList')
        ->has('events.data', 1)
        ->where('events.data.0.id', $matching->id));
})->with(['title', 'description', 'location'])->with(['lt', 'en']);

test('calendar search does not match JSON locale keys or missing translations', function (string $term): void {
    $tenant = Tenant::main();
    Calendar::factory()->for($tenant)->create([
        'title' => ['lt' => 'Mokymai', 'en' => 'Talk'],
        'description' => ['lt' => 'Tema', 'en' => 'Topic'],
        'location' => ['lt' => 'Sala', 'en' => 'Hall'],
        'is_draft' => false,
        'date' => now()->addDay(),
    ]);
    Calendar::factory()->for($tenant)->create([
        'title' => ['en' => 'English-only Scholarship'],
        'description' => [],
        'location' => [],
        'is_draft' => false,
        'date' => now()->addDay(),
    ]);

    $this->get(LocalizedRouteSlugs::route('calendar.list', ['search' => $term], 'lt'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->has('events.data', 0));
})->with(['lt', 'en', 'Scholarship']);

test('calendar search preserves publication language date tenant type and trash restrictions', function (): void {
    $tenant = Tenant::main();
    $type = EventType::factory()->create();
    $attributes = [
        'title' => ['lt' => 'Stipendija', 'en' => 'Scholarship'],
        'is_draft' => false,
        'is_international' => true,
        'date' => now()->addDay(),
        'tenant_id' => $tenant->id,
        'event_type_id' => $type->id,
    ];
    $matching = Calendar::factory()->create($attributes);

    foreach ([
        ['is_draft' => true],
        ['is_international' => false],
        ['date' => now()->subDay()],
        ['tenant_id' => Tenant::query()->whereKeyNot($tenant->id)->firstOrFail()->id],
        ['event_type_id' => EventType::factory()->create()->id],
        ['deleted_at' => now()],
    ] as $excluded) {
        Calendar::factory()->create([...$attributes, ...$excluded]);
    }

    $this->get(LocalizedRouteSlugs::route('calendar.list', [
        'search' => 'scholarship',
        'tenant' => $tenant->id,
        'type' => $type->id,
    ], 'en'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('events.data', 1)
        ->where('events.data.0.id', $matching->id));
});
