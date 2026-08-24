<?php

namespace App\Filament\Widgets;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class MerchantMenuReadiness extends StatsOverviewWidget
{
    protected ?string $heading = 'Menu readiness';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $tenant = $this->tenant();

        if (! $tenant) {
            return [];
        }

        $menuItems = MenuItem::query()
            ->where('restaurant_id', $tenant->id);

        $availableItems = (clone $menuItems)
            ->where('is_available', true)
            ->count();

        $totalItems = (clone $menuItems)->count();
        $unavailableItems = $totalItems - $availableItems;

        return [
            Stat::make('Available items', Number::format($availableItems))
                ->description(Number::format($totalItems).' total menu items')
                ->descriptionIcon(Heroicon::OutlinedRectangleStack)
                ->color($availableItems > 0 ? 'success' : 'warning'),
            Stat::make('Paused items', Number::format($unavailableItems))
                ->description('Items hidden from new orders')
                ->descriptionIcon(Heroicon::OutlinedPauseCircle)
                ->color($unavailableItems > 0 ? 'warning' : 'gray'),
        ];
    }

    private function tenant(): ?Restaurant
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Restaurant ? $tenant : null;
    }
}
