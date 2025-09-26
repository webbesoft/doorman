<?php

namespace Webbesoft\Doorman\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Webbesoft\Doorman\Models\UserAnalytic;

class CountriesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Visitors by Country';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '30s';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 days',
            '30' => '30 days',
            '90' => '90 days',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;

        $countries = UserAnalytic::whereBetween('date', [
            now()->subDays($days)->startOfDay(),
            now()->endOfDay(),
        ])
            ->whereNotNull('country')
            ->where('country', '!=', 'Unknown')
            ->selectRaw('country, COUNT(DISTINCT identifier) as visitors')
            ->groupBy('country')
            ->orderByDesc('visitors')
            ->limit(10)
            ->get();

        if ($countries->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                    ],
                ],
                'labels' => ['No Data'],
            ];
        }

        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Unique Visitors',
                    'data' => $countries->pluck('visitors')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $countries->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $countries->pluck('country')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
