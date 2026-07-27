<?php

use App\Services\LocationGeocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    $this->geocoder = new LocationGeocoder;
});

test('resolves a location to coordinates', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            [
                'lat' => '54.6871',
                'lon' => '25.2797',
                'display_name' => 'Universiteto g. 3, Vilnius',
            ],
        ]),
    ]);

    expect($this->geocoder->coordinates('Universiteto g. 3, Vilnius'))->toBe([
        'lat' => 54.6871,
        'lng' => 25.2797,
        'display_name' => 'Universiteto g. 3, Vilnius',
    ]);
});

test('returns null for an empty location without calling the API', function ($location) {
    Http::fake();

    expect($this->geocoder->coordinates($location))->toBeNull();

    Http::assertNothingSent();
})->with([null, '', '   ']);

test('returns null when the location cannot be resolved', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    expect($this->geocoder->coordinates('Neegzistuojanti vietovė'))->toBeNull();
});

test('returns null when the geocoding service fails', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(null, 503)]);

    expect($this->geocoder->coordinates('Vilnius'))->toBeNull();
});

test('returns null when the response is missing coordinates', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([['display_name' => 'Vilnius']]),
    ]);

    expect($this->geocoder->coordinates('Vilnius'))->toBeNull();
});

test('caches a successful lookup', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['lat' => '54.6872', 'lon' => '25.2797', 'display_name' => 'Vilnius'],
        ]),
    ]);

    $first = $this->geocoder->coordinates('Vilnius');
    $second = $this->geocoder->coordinates('  vilnius  ');

    expect($second)->toBe($first);

    Http::assertSentCount(1);
});

test('caches a failed lookup so a bad location is not retried on every view', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $this->geocoder->coordinates('Neegzistuojanti vietovė');
    $this->geocoder->coordinates('Neegzistuojanti vietovė');

    Http::assertSentCount(1);
});

test('identifies itself to Nominatim as its usage policy requires', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $this->geocoder->coordinates('Vilnius');

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent')
        && str_contains($request->header('User-Agent')[0], config('app.name')));
});
