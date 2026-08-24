<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class MerchantRevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue and order trend';

    protected ?string $description = 'Paid revenue and order volume for the last 14 days.';

    protected int|string|array $columnSpan = 1;

    protected string $color = 'success';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $tenant = $this->tenant();
        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo));

        if (! $tenant) {
            return [
                'datasets' => [],
                'labels' => $days->map(fn ($day): string => $day->format('M j'))->all(),
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $days
                        ->map(fn ($day): float => (float) Order::query()
                            ->where('restaurant_id', $tenant->id)
                            ->where('payment_status', 'paid')
                            ->whereDate('created_at', $day)
                            ->sum('total') / 100)
                        ->all(),
                ],
                [
                    'label' => 'Orders',
                    'data' => $days
                        ->map(fn ($day): int => Order::query()
                            ->where('restaurant_id', $tenant->id)
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

    private function tenant(): ?Restaurant
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Restaurant ? $tenant : null;
    }
}
