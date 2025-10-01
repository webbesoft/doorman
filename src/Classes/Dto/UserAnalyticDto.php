<?php

namespace Webbesoft\Doorman\Classes\Dto;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Webbesoft\Doorman\Services\AnalyticsService;
use Webbesoft\Doorman\Services\IPGeolocationService;

readonly class UserAnalyticDTO
{
    public readonly array $identifier;

    public readonly Carbon $today;

    public readonly string $ip;

    public readonly string $userAgent;

    public readonly string $page;

    public readonly ?string $ref;

    public readonly string $country;

    public function __construct(public AnalyticsService $analytics_service)
    {
        $this->identifier = [];
        $this->today = now();
        $this->ip = '192.168.1.1';
        $this->userAgent = 'Unknown';
        $this->page = '/';
        $this->ref = null;
        $this->country = 'Unknown';
    }

    public function fromRequest(Request $request): self
    {
        $this->identifier = $this->analytics_service->getUniqueIdentifier($request) ?? [];
        $this->today = now();
        $this->ip = $request->ip() ?? 'Unknown';
        $this->page = $request->path();
        $this->ref = $request->headers->get('referer') ?? null;
        $this->country = data_get(IPGeolocationService::getLocationData($request->ip()), 'country', 'Unknown');

        return $this;
    }
}
