<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

test('searching a place returns coordinates the map can pin', function () {
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([[
        'name' => 'Far Eastern University',
        'display_name' => 'Far Eastern University, Nicanor Reyes Street, Sampaloc, Manila, Philippines',
        'lat' => '14.6042',
        'lon' => '120.9867',
        'boundingbox' => ['14.6030', '14.6055', '120.9855', '120.9880'],
    ]], 200)]);

    $this->getJson(route('places.search', ['q' => 'FEU']))
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Far Eastern University')
        ->assertJsonPath('results.0.lat', 14.6042)
        ->assertJsonPath('results.0.lon', 120.9867);

    // "FEU" is expanded to the full university name before it is sent.
    Http::assertSent(fn ($request) => str_contains($request->url(), 'Far+Eastern+University')
        || str_contains($request->url(), 'Far%20Eastern%20University'));
});

test('a search with no matches returns an empty list', function () {
    Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([], 200)]);

    $this->getJson(route('places.search', ['q' => 'zzzz nowhere']))
        ->assertOk()
        ->assertJson(['results' => []]);
});

test('a search needs at least two characters', function () {
    $this->getJson(route('places.search', ['q' => 'a']))->assertStatus(422);
});

test('reverse geocoding returns an address for a pin', function () {
    Http::fake(['nominatim.openstreetmap.org/reverse*' => Http::response(['display_name' => 'Sampaloc, Manila'], 200)]);

    $this->getJson(route('places.reverse', ['lat' => 14.6042, 'lon' => 120.9867]))
        ->assertOk()
        ->assertJson(['address' => 'Sampaloc, Manila']);
});
