<?php

namespace Webbesoft\Doorman\Services;

use Illuminate\Support\Facades\Cache;

class IPGeolocationService
{
    public static function getLocationData(string $ip): array
    {
        $client = new \GuzzleHttp\Client;
        $obfuscatedIP = substr($ip, 0, strrpos($ip, '.')).'.0';

        $data = Cache::remember("ip_geo_{$obfuscatedIP}", 86400, function () use ($client, $ip) {
            $response = $client->get("http://ip-api.com/json/{$ip}");

            if ($response->getStatusCode() === 200) {
                $decoded = json_decode($response->getBody()->getContents(), true);
                if ($decoded && $decoded['status'] === 'success') {
                    return [
                        'country' => $decoded['country'] ?? 'Unknown',
                        'region' => $decoded['regionName'] ?? 'Unknown',
                        'city' => $decoded['city'] ?? 'Unknown',
                    ];
                }
            }

            return [
                'country' => 'Unknown',
                'region' => 'Unknown',
                'city' => 'Unknown',
            ];
        });

        return $data;
    }
}
