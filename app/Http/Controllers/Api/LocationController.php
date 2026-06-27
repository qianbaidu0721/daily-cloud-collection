<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Concerns\ApiResponse;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LocationService $locationService
    ) {}

    /**
     * 经纬度逆解析（高德，服务端调用）
     */
    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];

        $location = $this->locationService->reverseGeocode($lat, $lng);

        if ($location === null) {
            return $this->success([
                'city' => '',
                'district' => '',
                'location_city' => null,
                'display' => null,
            ], '未能解析位置');
        }

        $city = $location['city'];
        $district = $location['district'];

        $locationCity = $city !== '' && $district !== '' && $city !== $district
            ? "{$city} {$district}"
            : ($city !== '' ? $city : ($district !== '' ? $district : null));

        $display = $city !== '' && $district !== '' && $city !== $district
            ? "{$city} · {$district}"
            : ($city !== '' ? $city : ($district !== '' ? $district : null));

        return $this->success([
            'city' => $city,
            'district' => $district,
            'location_city' => $locationCity,
            'display' => $display,
        ]);
    }
}
