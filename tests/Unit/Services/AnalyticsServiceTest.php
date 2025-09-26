<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webbesoft\Doorman\Models\PageVisit;
use Webbesoft\Doorman\Models\UserAnalytic;
use Webbesoft\Doorman\Services\AnalyticsService;
use Webbesoft\Doorman\Services\IPGeolocationService;

beforeEach(function () {
    $this->service = new AnalyticsService;
});

it('tracks authenticated user visits', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(123);

    $this->mock(IPGeolocationService::class)
        ->shouldReceive('getLocationData')
        ->with('127.0.0.1')
        ->andReturn(['country' => 'US']);

    $request = Request::create('/test-page', 'GET');
    $request->headers->set('referer', 'https://example.com');

    $this->service->track($request);

    $this->assertDatabaseHas('user_analytics', [
        'identifier' => '123',
        'identifier_type' => 'user',
        'page' => '/test-page',
    ]);

    $this->assertDatabaseHas('page_visits', [
        'identifier' => '123',
        'identifier_type' => 'user',
        'page' => '/test-page',
    ]);
});

it('tracks guest session visits', function () {
    Auth::shouldReceive('check')->andReturn(false);

    $this->mock(IPGeolocationService::class)
        ->shouldReceive('getLocationData')
        ->andReturn(['country' => 'US']);

    $request = Request::create('/test-page', 'GET');
    $request->setLaravelSession(session());

    $this->service->track($request);

    expect(UserAnalytic::where('identifier_type', 'session')->exists())->toBeTrue();
    expect(PageVisit::where('identifier_type', 'session')->exists())->toBeTrue();
});

it('tracks IP-based visits when no session exists', function () {
    Auth::shouldReceive('check')->andReturn(false);

    $this->mock(IPGeolocationService::class)
        ->shouldReceive('getLocationData')
        ->with('127.0.0.1')
        ->andReturn(['country' => 'Unknown']);

    $request = Request::create('/test-page', 'GET');

    $this->service->track($request);

    expect(UserAnalytic::where('identifier_type', 'ip')->exists())->toBeTrue();
    expect(PageVisit::where('identifier_type', 'ip')->exists())->toBeTrue();
});

it('obfuscates IP addresses correctly', function () {
    $ip = '192.168.1.1';
    $salt = config('app.key').now()->format('Y-m-d');
    $expected = hash('sha256', $ip.$salt);

    $reflection = new ReflectionClass($this->service);
    $method = $reflection->getMethod('obfuscateIp');
    $method->setAccessible(true);

    $result = $method->invokeArgs($this->service, [$ip]);

    expect($result)->toBe($expected);
});

it('returns stats for given period', function () {
    $start = now()->subDays(7);
    $end = now();

    // Create some test data
    UserAnalytic::factory()->count(3)->create([
        'date' => now()->subDays(2)->format('Y-m-d H:i:s'),
    ]);

    $stats = $this->service->getStats($start, $end);

    expect($stats)->toHaveKeys(['unique_visitors', 'daily_stats', 'type_breakdown', 'period']);
    expect($stats['period']['start'])->toBe($start->format('Y-m-d'));
    expect($stats['period']['end'])->toBe($end->format('Y-m-d'));
});

it('returns today stats with breakdown', function () {
    // Create test data for today
    UserAnalytic::factory()->create([
        'identifier_type' => 'user',
        'date' => now()->format('Y-m-d H:i:s'),
    ]);

    UserAnalytic::factory()->create([
        'identifier_type' => 'session',
        'date' => now()->format('Y-m-d H:i:s'),
    ]);

    $stats = $this->service->getTodayStats();

    expect($stats)->toHaveKeys([
        'unique_visitors',
        'authenticated_users',
        'guest_sessions',
        'unknown_visitors',
    ]);
});

it('calculates weekly growth percentage', function () {
    // Create data for this week
    UserAnalytic::factory()->count(3)->create([
        'date' => now()->startOfWeek()->addDay()->format('Y-m-d H:i:s'),
    ]);

    // Create data for last week
    UserAnalytic::factory()->count(2)->create([
        'date' => now()->subWeek()->startOfWeek()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $growth = $this->service->getWeeklyGrowth();

    expect($growth)->toHaveKeys(['this_week', 'last_week', 'growth_percentage']);
    expect($growth['growth_percentage'])->toBe(50.0);
});

it('handles zero growth when last week had no visitors', function () {
    // Only create data for this week
    UserAnalytic::factory()->count(2)->create([
        'date' => now()->startOfWeek()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $growth = $this->service->getWeeklyGrowth();

    expect($growth['growth_percentage'])->toBe(100);
});

it('prioritizes user ID over session ID', function () {
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('id')->andReturn(456);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(session());

    $reflection = new ReflectionClass($this->service);
    $method = $reflection->getMethod('getUniqueIdentifier');
    $method->setAccessible(true);

    $identifier = $method->invokeArgs($this->service, [$request]);

    expect($identifier)->toBe([
        'value' => '456',
        'type' => 'user',
    ]);
});

it('returns session ID when user not authenticated', function () {
    Auth::shouldReceive('check')->andReturn(false);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(session());

    $reflection = new ReflectionClass($this->service);
    $method = $reflection->getMethod('getUniqueIdentifier');
    $method->setAccessible(true);

    $identifier = $method->invokeArgs($this->service, [$request]);

    expect($identifier['type'])->toBe('session');
    expect($identifier['value'])->toBeString();
});

it('returns null when no identifier available', function () {
    Auth::shouldReceive('check')->andReturn(false);

    $request = Request::create('/test', 'GET', [], [], [], [
        'REMOTE_ADDR' => null,
    ]);

    $reflection = new ReflectionClass($this->service);
    $method = $reflection->getMethod('getUniqueIdentifier');
    $method->setAccessible(true);

    $identifier = $method->invokeArgs($this->service, [$request]);

    expect($identifier)->toBeNull();
});
