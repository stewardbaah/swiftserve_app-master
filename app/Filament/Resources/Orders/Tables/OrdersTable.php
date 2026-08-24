<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('Guest'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivery' => 'info',
                        'pickup' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'gray',
                        'preparing' => 'warning',
                        'ready_for_pickup', 'out_for_delivery' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total')
                    ->money('GHS', divideBy: 100)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'received' => 'Received',
                        'preparing' => 'Preparing',
                        'ready_for_pickup' => 'Ready for Pickup',
                        'out_for_delivery' => 'Out for Delivery',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        'delivery' => 'Delivery',
                        'pickup' => 'Pickup',
                    ]),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === 'received')
                    ->action(function (Order $record) {
                        $record->update(['status' => 'preparing']);
                        Notification::make()
                            ->title('Order accepted')
                            ->success()
                            ->send();
                    }),
                Action::make('ready')
                    ->label('Mark Ready')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->visible(fn (Order $record) => $record->status === 'preparing')
                    ->action(function (Order $record) {
                        $nextStatus = $record->type === 'pickup' ? 'ready_for_pickup' : 'out_for_delivery';
                        $record->update(['status' => $nextStatus]);
                        Notification::make()
                            ->title('Order marked as ready')
                            ->success()
                            ->send();
                    }),
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record) => in_array($record->status, ['ready_for_pickup', 'out_for_delivery']))
                    ->action(function (Order $record) {
                        $record->update([
                            'status' => 'completed',
                            'payment_status' => 'paid',
                        ]);
                        Notification::make()
                            ->title('Order completed')
                            ->success()
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => ! in_array($record->status, ['completed', 'cancelled']))
                    ->action(function (Order $record) {
                        $record->update(['status' => 'cancelled']);
                        Notification::make()
                            ->title('Order cancelled')
                            ->danger()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
