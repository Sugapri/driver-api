<?php
// C:\laragon\www\driver-api\app\Http\Controllers\LocationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    /**
     * Proxy for Nominatim Search API
     */
    public function search(Request $request)
    {
        $query = $request->query('q');
        if (empty($query)) {
            return response()->json([], 200);
        }

        $response = Http::withHeaders([
            'User-Agent' => 'GojekCloneID/2.0 (proxy-server@laravel.com)',
            'Accept-Language' => 'id',
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q'              => $query,
            'format'         => 'json',
            'limit'          => 4,
            'countrycodes'   => 'id',
            'addressdetails' => 1,
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Gagal mengambil data dari Nominatim',
            'status' => $response->status()
        ], $response->status());
    }

    /**
     * Proxy for Nominatim Reverse Geocoding API
     */
    public function reverse(Request $request)
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');

        if (empty($lat) || empty($lon)) {
            return response()->json(['error' => 'Latitude dan Longitude diperlukan'], 400);
        }

        $response = Http::withHeaders([
            'User-Agent' => 'GojekCloneID/2.0 (proxy-server@laravel.com)',
            'Accept-Language' => 'id',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat'            => $lat,
            'lon'            => $lon,
            'format'         => 'json',
            'addressdetails' => 1,
            'namedetails'    => 1,
            'zoom'           => 18,
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Gagal melakukan reverse geocoding',
            'status' => $response->status()
        ], $response->status());
    }
}
