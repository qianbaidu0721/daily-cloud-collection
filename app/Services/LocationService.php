<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * 根据经纬度逆解析地址
     *
     * @param  float  $lat  纬度
     * @param  float  $lng  经度
     * @return array{city: string, district: string}|null
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $key = config('amap.key');

        if (empty($key)) {
            Log::debug('Amap key not configured, skip reverse geocoding');

            return null;
        }

        try {
            $response = Http::timeout((int) config('amap.timeout', 5))
                ->retry(1, 200, throw: false)
                ->get(config('amap.geocode_url'), [
                    'key' => $key,
                    'location' => sprintf('%.6f,%.6f', $lng, $lat),
                    'extensions' => 'base',
                    'output' => 'json',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Amap reverse geocode connection failed', [
                'lat' => $lat,
                'lng' => $lng,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Amap reverse geocode request failed', [
                'lat' => $lat,
                'lng' => $lng,
                'status' => $response->status(),
            ]);

            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || (int) ($payload['status'] ?? 0) !== 1) {
            Log::warning('Amap reverse geocode returned error', [
                'lat' => $lat,
                'lng' => $lng,
                'info' => $payload['info'] ?? 'unknown',
            ]);

            return null;
        }

        $addressComponent = $payload['regeocode']['addressComponent'] ?? null;

        if (! is_array($addressComponent)) {
            return null;
        }

        return $this->normalizeAddressComponent($addressComponent);
    }

    /**
     * 解析并格式化为 location_city 存储值
     *
     * @param  float  $lat  纬度
     * @param  float  $lng  经度
     */
    public function resolveCityName(float $lat, float $lng): ?string
    {
        $location = $this->reverseGeocode($lat, $lng);

        if ($location === null) {
            return null;
        }

        $city = $location['city'];
        $district = $location['district'];

        if ($city !== '' && $district !== '' && $city !== $district) {
            return "{$city} {$district}";
        }

        return $city !== '' ? $city : ($district !== '' ? $district : null);
    }

    /**
     * 标准化高德 addressComponent 为 city / district
     *
     * @param  array<string, mixed>  $component
     * @return array{city: string, district: string}
     */
    private function normalizeAddressComponent(array $component): array
    {
        $city = $this->toString($component['city'] ?? '');
        $province = $this->toString($component['province'] ?? '');
        $district = $this->toString($component['district'] ?? '');

        if ($city === '') {
            $city = $province;
        }

        return [
            'city' => $this->trimAdministrativeSuffix($city),
            'district' => $this->trimAdministrativeSuffix($district),
        ];
    }

    /**
     * 高德部分字段可能返回空数组
     */
    private function toString(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value) && $value === []) {
            return '';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * 去除省市区等行政后缀，便于展示
     */
    private function trimAdministrativeSuffix(string $name): string
    {
        $suffixes = ['特别行政区', '自治区', '自治州', '地区', '盟', '省', '市', '区', '县'];

        foreach ($suffixes as $suffix) {
            if (str_ends_with($name, $suffix) && mb_strlen($name) > mb_strlen($suffix)) {
                $name = mb_substr($name, 0, -mb_strlen($suffix));
            }
        }

        return trim($name);
    }
}
