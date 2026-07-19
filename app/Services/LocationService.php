<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LocationService
{
    /**
     * Reverse geocode latitude & longitude using OpenStreetMap Nominatim.
     */
    public static function reverseGeocode(
        float $latitude,
        float $longitude
    ): array {
        $response = Http::withHeaders([
            'User-Agent' => 'AgencyOS/1.0',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'format' => 'jsonv2',
            'lat'    => $latitude,
            'lon'    => $longitude,
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'address' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'google_maps_link' => null,
            ];
        }

        $data = $response->json();

        return [
            'success' => true,

            'address' => $data['display_name'] ?? null,

            'latitude' => $latitude,

            'longitude' => $longitude,

            'google_maps_link' =>
                "https://www.google.com/maps?q={$latitude},{$longitude}",
        ];
    }
}