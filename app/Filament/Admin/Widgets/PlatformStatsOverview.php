<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Complaint;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Platform health';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $todayRevenue = Order::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total');

        $monthlyRevenue = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total');

        $activeOrders = Order::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $pendingRestaurants = Restaurant::query()
            ->where('is_approved', false)
            ->count();

        $averageRating = Review::query()->avg('rating');

        return [
            Stat::make('Today revenue', $this->money($todayRevenue))
                ->description($this->money($monthlyRevenue).' in the last 30 days')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->chart($this->dailyRevenueChart()),
            Stat::make('Active orders', Number::format($activeOrders))
                ->description('Orders still moving through the kitchen or delivery flow')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($activeOrders > 0 ? 'warning' : 'gray'),
            Stat::make('Restaurants', Number::format(Restaurant::query()->count()))
                ->description(Number::format($pendingRestaurants).' awaiting approval')
                ->descriptionIcon(Heroicon::OutlinedBuildingStorefront)
                ->color($pendingRestaurants > 0 ? 'warning' : 'success'),
            Stat::make('Customers', Number::format(User::query()->where('is_admin', false)->count()))
                ->description(Number::format(User::query()->where('is_admin', true)->count()).' admins')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('info'),
            Stat::make('Average rating', $averageRating ? Number::format($averageRating, precision: 1).'/5' : 'No reviews')
                ->description(Number::format(Review::query()->count()).' reviews submitted')
                ->descriptionIcon(Heroicon::OutlinedStar)
                ->color('primary'),
            Stat::make('Open complaints', Number::format($this->openComplaintsCount()))
                ->description(Number::format(Complaint::query()->count()).' total complaints')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($this->openComplaintsCount() > 0 ? 'danger' : 'success'),
        ];
    }

    /**
     * @return array<float>
     */
    private function dailyRevenueChart(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): float => (float) Order::query()
                ->where('payment_status', 'paid')
                ->whereDate('created_at', now()->subDays($daysAgo))
                ->sum('total') / 100)
            ->all();
    }

    private function money(int|float $amount): string
    {
        return Number::currency((float) $amount / 100, 'GHS');
    }

    private function openComplaintsCount(): int
    {
        return Complaint::query()
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
    }
}
