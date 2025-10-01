<?php

namespace Webbesoft\Doorman\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webbesoft\Doorman\Classes\Dto\UserAnalyticDTO;
use Webbesoft\Doorman\Models\PageVisit;
use Webbesoft\Doorman\Models\UserAnalytic;

class AnalyticsService
{
    public function track(Request $request): void
    {
        $userAnalyticDto = new UserAnalyticDTO($this);
        $userAnalyticDto->fromRequest($request);

        if ($userAnalyticDto->identifier) {
            $this->recordVisit($userAnalyticDto);
        }
    }

    /**
     * Get the best unique identifier for the visitor
     * Priority: user_id > session_id > hashed_ip
     */
    public function getUniqueIdentifier(Request $request): ?array
    {
        if (Auth::check()) {
            return [
                'value' => (string) Auth::id(),
                'type' => 'user',
            ];
        }

        if ($request->hasSession() && $request->session()->getId()) {
            return [
                'value' => $request->session()->getId(),
                'type' => 'session',
            ];
        }

        if ($request->ip()) {
            return [
                'value' => $this->obfuscateIp($request->ip()),
                'type' => 'ip',
            ];
        }

        return null;
    }

    /**
     * Create a one-way hash of the IP address for privacy
     */
    protected function obfuscateIp(string $ip): string
    {
        $truncatedIp = preg_replace('/\.\d+$/', '.0', $ip);

        return hash_hmac('sha256', $truncatedIp, config('app.key'));
    }

    protected function recordVisit(UserAnalyticDTO $user_analytic_dto): void
    {
        UserAnalytic::updateOrCreate(
            [
                'identifier' => $user_analytic_dto->identifier['value'],
                'identifier_type' => $user_analytic_dto->identifier['type'],
            ],
            [
                'date' => $user_analytic_dto->today->toDateString(),
                'page' => $user_analytic_dto->page,
                'ref' => $user_analytic_dto->ref,
                'country' => $user_analytic_dto->country,
            ]
        );

        PageVisit::create([
            'page' => $user_analytic_dto->page,
            'visited_at' => $user_analytic_dto->today,
            'identifier' => $user_analytic_dto->identifier['value'],
            'identifier_type' => $user_analytic_dto->identifier['type'],
        ]);
    }

    public function getStats(?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = $start ?: now()->subDays(30);
        $end = $end ?: now();

        return [
            'unique_visitors' => UserAnalytic::getUniqueVisitorsForPeriod($start, $end),
            'daily_stats' => UserAnalytic::getDailyStats($start, $end),
            'type_breakdown' => UserAnalytic::getTypeBreakdown($start, $end),
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
        ];
    }

    public function getTodayStats(): array
    {
        $endOfDay = now()->endOfDay();
        $today = now()->startOfDay();
        $typeBreakdown = UserAnalytic::getTypeBreakdown($today, $endOfDay);

        return [
            'unique_visitors' => UserAnalytic::getUniqueVisitorsForDate($today),
            'authenticated_users' => $typeBreakdown['user'] ?? 0,
            'guest_sessions' => $typeBreakdown['session'] ?? 0,
            'unknown_visitors' => $typeBreakdown['ip'] ?? 0,
        ];
    }

    public function getWeeklyGrowth(): array
    {
        $thisWeek = UserAnalytic::thisWeek()->count();
        $lastWeek = UserAnalytic::byDateRange(
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        )->count();

        return [
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'growth_percentage' => $lastWeek > 0
                ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1)
                : ($thisWeek > 0 ? 100 : 0),
        ];
    }
}
