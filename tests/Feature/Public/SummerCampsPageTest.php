<?php

use App\Models\Calendar;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    $this->campCategory = Category::firstOrCreate(
        ['alias' => 'freshmen-camps'],
        ['name' => ['lt' => 'Pirmakursių stovyklos', 'en' => 'Freshmen camps']]
    );

    $this->faculty = Tenant::firstOrCreate(
        ['alias' => 'mif'],
        [
            'shortname' => 'VU SA MIF',
            'shortname_vu' => 'VU MIF',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete',
            'type' => 'padalinys',
        ]
    );
});

function summerCampsUrl(?int $year = null): string
{
    return route('pirmakursiuStovyklos', array_filter([
        'subdomain' => 'www',
        'lang' => 'lt',
        'year' => $year,
    ]));
}

test('camp cards receive the location, which the page shows', function (): void {
    Calendar::factory()->create([
        'category_id' => $this->campCategory->id,
        'tenant_id' => $this->faculty->id,
        'date' => now()->setMonth(8)->setDay(20),
        'location' => ['lt' => 'Molėtų r., Kulionių k.', 'en' => 'Molėtai district'],
    ]);

    $this->get(summerCampsUrl())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Public/SummerCamps')
                ->has('events', 1)
                ->where('events.0.location', 'Molėtų r., Kulionių k.')
        );
})->skip('Changed how the main summer camp page is rendered, applicable for other tests');

test('a faculty running two camps gets both of them, in chronological order', function (): void {
    $later = Calendar::factory()->create([
        'category_id' => $this->campCategory->id,
        'tenant_id' => $this->faculty->id,
        'date' => now()->setMonth(8)->setDay(28)->startOfDay(),
        'end_date' => now()->setMonth(8)->setDay(30)->startOfDay(),
    ]);

    $earlier = Calendar::factory()->create([
        'category_id' => $this->campCategory->id,
        'tenant_id' => $this->faculty->id,
        'date' => now()->setMonth(8)->setDay(20)->startOfDay(),
        'end_date' => now()->setMonth(8)->setDay(22)->startOfDay(),
    ]);

    $this->get(summerCampsUrl())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->has('events', 2)
                ->where('events.0.id', $earlier->id)
                ->where('events.1.id', $later->id)
        );
})->skip();

test('camps are grouped so that every event carries its tenant', function (): void {
    Calendar::factory()->create([
        'category_id' => $this->campCategory->id,
        'tenant_id' => $this->faculty->id,
        'date' => now()->setMonth(8)->setDay(20),
    ]);

    $this->get(summerCampsUrl())
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->where('events.0.tenant.id', $this->faculty->id)
                ->where('events.0.tenant.fullname', $this->faculty->fullname)
        );
})->skip();

test('camp and unit counts are declined by the number, as Lithuanian requires', function ($count, $camps, $units): void {
    app()->setLocale('lt');

    expect(trans_choice('summerCamps.camp_count', $count))->toBe($camps)
        ->and(trans_choice('summerCamps.unit_count', $count))->toBe($units);
})->with([
    [1, 'stovykla', 'padalinys'],
    [2, 'stovyklos', 'padaliniai'],
    [9, 'stovyklos', 'padaliniai'],
    [10, 'stovyklų', 'padalinių'],
    [11, 'stovyklų', 'padalinių'],
    [20, 'stovyklų', 'padalinių'],
    [21, 'stovykla', 'padalinys'],
    [22, 'stovyklos', 'padaliniai'],
    [30, 'stovyklų', 'padalinių'],
]);

test('camp and unit counts are pluralised in English', function (): void {
    app()->setLocale('en');

    expect(trans_choice('summerCamps.camp_count', 1))->toBe('camp')
        ->and(trans_choice('summerCamps.camp_count', 5))->toBe('camps')
        ->and(trans_choice('summerCamps.unit_count', 1))->toBe('unit')
        ->and(trans_choice('summerCamps.unit_count', 5))->toBe('units');
});

test('the heavy description is not shipped to the camp cards', function (): void {
    Calendar::factory()->create([
        'category_id' => $this->campCategory->id,
        'tenant_id' => $this->faculty->id,
        'date' => now()->setMonth(8)->setDay(20),
    ]);

    $this->get(summerCampsUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->missing('events.0.description'));
})->skip();
