<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tenantId = Filament::getTenant()?->id;

        return [
            'all' => Tab::make('All Orders'),
            'received' => Tab::make('Received')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'received'))
                ->badge(fn () => Order::where('restaurant_id', $tenantId)->where('status', 'received')->count()),
            'preparing' => Tab::make('Preparing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'preparing'))
                ->badge(fn () => Order::where('restaurant_id', $tenantId)->where('status', 'preparing')->count()),
            'ready_out' => Tab::make('Ready / Out')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['ready_for_pickup', 'out_for_delivery']))
                ->badge(fn () => Order::where('restaurant_id', $tenantId)->whereIn('status', ['ready_for_pickup', 'out_for_delivery'])->count()),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}
