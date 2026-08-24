<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class OrderStatusChart extends ChartWidget
{
    protected ?string $heading = 'Order status mix';

    protected ?string $description = 'Current distribution across all orders.';

    protected int|string|array $columnSpan = 1;

    protected string $color = 'warning';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $statuses = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'status');

        return [
            'datasets' => [
                [
                    'data' => $statuses->values()->all(),
                ],
            ],
            'labels' => $statuses
                ->keys()
                ->map(fn (string $status): string => Str::of($status)->replace('_', ' ')->title()->toString())
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
