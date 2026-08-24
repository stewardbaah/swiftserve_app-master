<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue trend';

    protected ?string $description = 'Paid order totals for the last 14 days.';

    protected int|string|array $columnSpan = 1;

    protected string $color = 'success';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo));

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $days
                        ->map(fn ($day): float => (float) Order::query()
                            ->where('payment_status', 'paid')
                            ->whereDate('created_at', $day)
                            ->sum('total') / 100)
                        ->all(),
                ],
                [
                    'label' => 'Orders',
                    'data' => $days
                        ->map(fn ($day): int => Order::query()
                            ->whereDate('created_at', $day)
                            ->count())
                        ->all(),
                ],
            ],
            'labels' => $days
                ->map(fn ($day): string => $day->format('M j'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
