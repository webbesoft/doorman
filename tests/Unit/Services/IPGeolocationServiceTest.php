<?php

it('should return location data for a valid IP', function () {
    $ip = '8.8.8.8';

    $locationData = \Webbesoft\Doorman\Services\IPGeolocationService::getLocationData($ip);

    expect($locationData)->toBeArray();
    expect($locationData)->toHaveKeys(['country', 'region', 'city']);
    expect($locationData['country'])->not->toBe('Unknown');
});

it('should return Unknown for an invalid IP', function () {
    $ip = '999.999.999.999';

    $locationData = \Webbesoft\Doorman\Services\IPGeolocationService::getLocationData($ip);

    expect($locationData)->toBeArray();
    expect($locationData)->toHaveKeys(['country', 'region', 'city']);
    expect($locationData['country'])->toBe('Unknown');
});

it('should cache the location data', function () {
    $ip = '8.8.8.8';

    $locationData1 = \Webbesoft\Doorman\Services\IPGeolocationService::getLocationData($ip);

    $locationData2 = \Webbesoft\Doorman\Services\IPGeolocationService::getLocationData($ip);
    expect($locationData1)->toEqual($locationData2);
});
