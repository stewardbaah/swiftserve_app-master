<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrdersWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent orders needing attention')
            ->query(fn (): Builder => Order::query()
                ->with(['restaurant', 'user'])
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->latest())
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->placeholder('Guest')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'info',
                        'preparing' => 'warning',
                        'ready_for_pickup', 'out_for_delivery' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('total')
                    ->money('GHS', divideBy: 100)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
