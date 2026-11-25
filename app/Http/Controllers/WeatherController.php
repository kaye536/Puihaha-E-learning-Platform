<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-side proxy for OpenWeather. The secret key stays in .env and is
 * never sent to the browser.
 */
class WeatherController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $key = config('services.openweather.key');

        if (blank($key)) {
            return response()->json(['message' => 'Weather service is not configured.'], 503);
        }

        // Round to 2 decimals (~1 km) so nearby clicks share one cached result.
        $lat = round((float) $data['lat'], 2);
        $lon = round((float) $data['lon'], 2);

        try {
            $weather = Cache::remember("weather:{$lat}:{$lon}", now()->addMinutes(10), function () use ($lat, $lon, $key) {
                return Http::timeout(10)
                    ->get('https://api.openweathermap.org/data/2.5/weather', [
                        'lat' => $lat,
                        'lon' => $lon,
                        'units' => 'metric',
                        'appid' => $key,
                    ])
                    ->throw()
                    ->json();
            });
        } catch (Throwable $e) {
            Log::warning('OpenWeather request failed: '.$e->getMessage());

            return response()->json(['message' => 'Weather is currently unavailable.'], 502);
        }

        return response()->json([
            'place' => $weather['name'] ?? 'Selected location',
            'temperature' => $weather['main']['temp'] ?? null,
            'description' => $weather['weather'][0]['description'] ?? null,
            'humidity' => $weather['main']['humidity'] ?? null,
        ]);
    }
}
