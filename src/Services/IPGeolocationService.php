<?php

namespace Webbesoft\Doorman\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IPGeolocationService
{
    public static function getLocationData(string $ip): array
    {
        try {
            $client = new \GuzzleHttp\Client;
            $obfuscatedIP = substr($ip, 0, strrpos($ip, '.')).'.0';

            $data = Cache::remember("ip_geo_{$obfuscatedIP}", 86400, function () use ($client, $ip) {
                $response = $client->get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");

                if ($response->getStatusCode() === 200) {
                    $decoded = json_decode($response->getBody()->getContents(), true);
                    Log::info('IP Geolocation response', ['response' => $decoded]);
                    if ($decoded && data_get($decoded, 'status') === 'success') {
                        return [
                            'country' => data_get($decoded, 'country', 'Unknown'),
                            'region' => data_get($decoded, 'regionName', 'Unknown'),
                            'city' => data_get($decoded, 'city', 'Unknown'),
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
        } catch (\Exception $e) {
            // fail silently
            return [
                'country' => 'Unknown',
                'region' => 'Unknown',
                'city' => 'Unknown',
            ];
        }
    }
}
