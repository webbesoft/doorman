<?php

namespace Webbesoft\Doorman\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webbesoft\Doorman\Models\UserAnalytic;
use Webbesoft\Doorman\Services\AnalyticsService;

class AnalyticsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $period = $this->getDateRange();
        $analyticsService = app(AnalyticsService::class);
        $todayStats = $analyticsService->getTodayStats();
        $weeklyGrowth = $analyticsService->getWeeklyGrowth();

        return [
            Stat::make('Unique Visitors Today', $todayStats['unique_visitors'])
                ->description($this->getGrowthDescription($weeklyGrowth))
                ->descriptionIcon($this->getGrowthIcon($weeklyGrowth))
                ->color($this->getGrowthColor($weeklyGrowth)),

            Stat::make('Authenticated Users', $todayStats['authenticated_users'])
                ->description('Logged in users today')
                ->descriptionIcon('heroicon-m-user')
                ->color('success'),

            Stat::make('Guest Visitors', $todayStats['guest_sessions'] + $todayStats['unknown_visitors'])
                ->description('Anonymous visitors today')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            $this->getTopCountryStat($period),
        ];
    }

    protected function getDateRange(): array
    {
        return [
            'start' => now()->subDays(30)->startOfDay(),
            'end' => now()->endOfDay(),
        ];
    }

    protected function getGrowthDescription(array $growth): string
    {
        $percentage = $growth['growth_percentage'];

        if ($percentage == 0) {
            return 'No change from last week';
        }

        $direction = $percentage > 0 ? 'increase' : 'decrease';

        return sprintf('%.1f%% %s from last week', abs($percentage), $direction);
    }

    protected function getGrowthIcon(array $growth): string
    {
        $percentage = $growth['growth_percentage'];

        if ($percentage > 0) {
            return 'heroicon-m-arrow-trending-up';
        } elseif ($percentage < 0) {
            return 'heroicon-m-arrow-trending-down';
        }

        return 'heroicon-m-minus';
    }

    protected function getGrowthColor(array $growth): string
    {
        $percentage = $growth['growth_percentage'];

        if ($percentage > 0) {
            return 'success';
        } elseif ($percentage < 0) {
            return 'danger';
        }

        return 'gray';
    }

    protected function getTopCountryStat(array $period): Stat
    {
        $topCountry = UserAnalytic::whereBetween('date', [$period['start'], $period['end']])
            ->whereNotNull('country')
            ->where('country', '!=', 'Unknown')
            ->selectRaw('country, COUNT(DISTINCT identifier) as visitors')
            ->groupBy('country')
            ->orderByDesc('visitors')
            ->first();

        if (! $topCountry) {
            return Stat::make('Top Country', 'No data')
                ->description('No country data available')
                ->color('gray');
        }

        return Stat::make('Top Country', $topCountry->country)
            ->description(number_format($topCountry->visitors).' unique visitors')
            ->descriptionIcon('heroicon-m-globe-alt')
            ->color('primary');
    }
}
