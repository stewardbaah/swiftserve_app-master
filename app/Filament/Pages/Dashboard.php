<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MerchantActiveOrdersWidget;
use App\Filament\Widgets\MerchantApprovalWidget;
use App\Filament\Widgets\MerchantMenuReadiness;
use App\Filament\Widgets\MerchantRevenueTrendChart;
use App\Filament\Widgets\MerchantStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Merchant Dashboard';

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            MerchantApprovalWidget::class,
            MerchantStatsOverview::class,
            MerchantRevenueTrendChart::class,
            MerchantMenuReadiness::class,
            MerchantActiveOrdersWidget::class,
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
