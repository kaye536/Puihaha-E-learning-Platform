<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-side proxy for OpenStreetMap Nominatim (no API key needed).
 * Powers the "search a place and pin it" box on the location map.
 */
class PlaceSearchController extends Controller
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org';

    /** Common Philippine abbreviations that Nominatim does not match on its own. */
    private const ALIASES = [
        'feu' => 'Far Eastern University',
        'ust' => 'University of Santo Tomas',
        'dlsu' => 'De La Salle University',
        'admu' => 'Ateneo de Manila University',
        'ateneo' => 'Ateneo de Manila University',
        'up diliman' => 'University of the Philippines Diliman',
        'upd' => 'University of the Philippines Diliman',
        'pup' => 'Polytechnic University of the Philippines',
        'plm' => 'Pamantasan ng Lungsod ng Maynila',
        'nu' => 'National University Manila',
        'ue' => 'University of the East',
        'mapua' => 'Mapua University',
        'adamson' => 'Adamson University',
        'ccp' => 'Cultural Center of the Philippines',
        'naia' => 'Ninoy Aquino International Airport',
        'moa' => 'SM Mall of Asia',
        'bgc' => 'Bonifacio Global City',
        'qc' => 'Quezon City',
    ];

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:200']]);

        $query = trim($data['q']);
        $key = mb_strtolower($query);

        // Try the plain text, then the expanded abbreviation, then the whole world.
        $attempts = [[$query, 'ph']];
        if (isset(self::ALIASES[$key])) {
            array_unshift($attempts, [self::ALIASES[$key], 'ph']);
        }
        $attempts[] = [$query, null];

        try {
            foreach ($attempts as [$text, $country]) {
                $results = $this->lookup($text, $country);
                if ($results !== []) {
                    return response()->json(['results' => $results]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('Place search failed: '.$e->getMessage());

            return response()->json(['message' => 'Place search is currently unavailable.'], 502);
        }

        return response()->json(['results' => []]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = round((float) $data['lat'], 5);
        $lon = round((float) $data['lon'], 5);

        try {
            $json = Cache::remember("reverse:{$lat}:{$lon}", now()->addDay(), function () use ($lat, $lon) {
                return $this->client()->get(self::ENDPOINT.'/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'jsonv2',
                    'zoom' => 18,
                    'accept-language' => 'en',
                ])->throw()->json();
            });
        } catch (Throwable $e) {
            Log::warning('Reverse geocode failed: '.$e->getMessage());

            return response()->json(['address' => null], 200);
        }

        return response()->json(['address' => $json['display_name'] ?? null]);
    }

    /** @return array<int, array<string, mixed>> */
    private function lookup(string $text, ?string $country): array
    {
        $cacheKey = 'places:'.md5(mb_strtolower($text).'|'.($country ?? 'any'));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($text, $country) {
            $params = [
                'q' => $text,
                'format' => 'jsonv2',
                'limit' => 6,
                'addressdetails' => 0,
                'accept-language' => 'en',
            ];
            if ($country) {
                $params['countrycodes'] = $country;
            }

            $rows = $this->client()->get(self::ENDPOINT.'/search', $params)->throw()->json();

            return collect($rows)->map(function ($row) {
                $box = $row['boundingbox'] ?? null; // [south, north, west, east]

                return [
                    'name' => $row['name'] ?: explode(',', $row['display_name'])[0],
                    'address' => $row['display_name'],
                    'lat' => (float) $row['lat'],
                    'lon' => (float) $row['lon'],
                    'bounds' => $box ? [[(float) $box[0], (float) $box[2]], [(float) $box[1], (float) $box[3]]] : null,
                ];
            })->values()->all();
        });
    }

    private function client()
    {
        // Nominatim's usage policy requires an identifying User-Agent.
        return Http::timeout(10)->withHeaders([
            'User-Agent' => config('app.name', 'PuihahaPlatform').' membership platform',
        ]);
    }
}
