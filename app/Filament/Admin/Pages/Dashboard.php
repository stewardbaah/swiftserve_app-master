<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\LatestOrdersWidget;
use App\Filament\Admin\Widgets\OrderStatusChart;
use App\Filament\Admin\Widgets\PlatformStatsOverview;
use App\Filament\Admin\Widgets\RevenueTrendChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Admin Dashboard';

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            PlatformStatsOverview::class,
            RevenueTrendChart::class,
            OrderStatusChart::class,
            LatestOrdersWidget::class,
        ];
    }

    /**
     * @return int|array<string, int|null>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
