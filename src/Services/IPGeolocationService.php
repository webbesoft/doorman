<?php

namespace Webbesoft\Doorman\Services;

use Illuminate\Support\Facades\Cache;

class IPGeolocationService
{
    public static function getLocationData(string $ip): array
    {
        $client = new \GuzzleHttp\Client;
        $obfuscatedIP = substr($ip, 0, strrpos($ip, '.')).'.0';
        $response = Cache::remember("ip_geo_{$obfuscatedIP}", 86400, function () use ($client, $ip) {
            return $client->get("http://ip-api.com/json/{$ip}");
        });

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'region' => $data['regionName'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                ];
            }
        }

        return [
            'country' => 'Unknown',
            'region' => 'Unknown',
            'city' => 'Unknown',
        ];
    }
}
