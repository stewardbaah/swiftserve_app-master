<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class MerchantStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Today at a glance';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $tenant = $this->tenant();

        if (! $tenant) {
            return [];
        }

        $todayOrders = $this->ordersQuery()
            ->whereDate('created_at', today())
            ->count();

        $todayRevenue = $this->ordersQuery()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total');

        $activeOrders = $this->ordersQuery()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $averageTicket = $todayOrders > 0 ? $todayRevenue / $todayOrders : 0;

        return [
            Stat::make('Today revenue', $this->money($todayRevenue))
                ->description($this->money($averageTicket).' average ticket')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->chart($this->dailyRevenueChart()),
            Stat::make('Orders today', Number::format($todayOrders))
                ->description(Number::format($activeOrders).' active right now')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($activeOrders > 0 ? 'warning' : 'gray'),
            Stat::make('Rating', Number::format($tenant->rating, precision: 1).'/5')
                ->description(Number::format($tenant->rating_count).' customer ratings')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->color('primary'),
            Stat::make('Service modes', $this->serviceModes($tenant))
                ->description($tenant->is_active ? 'Store is active' : 'Store is inactive')
                ->descriptionIcon(Heroicon::OutlinedBuildingStorefront)
                ->color($tenant->is_active && $tenant->is_approved ? 'success' : 'warning'),
        ];
    }

    private function ordersQuery(): Builder
    {
        return Order::query()
            ->where('restaurant_id', $this->tenant()?->id);
    }

    /**
     * @return array<float>
     */
    private function dailyRevenueChart(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): float => (float) $this->ordersQuery()
                ->where('payment_status', 'paid')
                ->whereDate('created_at', now()->subDays($daysAgo))
                ->sum('total') / 100)
            ->all();
    }

    private function money(int|float $amount): string
    {
        return Number::currency((float) $amount / 100, 'GHS');
    }

    private function serviceModes(Restaurant $restaurant): string
    {
        return collect([
            $restaurant->offers_delivery ? 'Delivery' : null,
            $restaurant->offers_pickup ? 'Pickup' : null,
        ])
            ->filter()
            ->join(' + ') ?: 'Paused';
    }

    private function tenant(): ?Restaurant
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Restaurant ? $tenant : null;
    }
}
