<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $pluralModelLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('payment_status', ['paid', 'pending', 'failed']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order_number')
                ->disabled(),
            TextInput::make('user.name')
                ->label('Customer')
                ->placeholder('Guest')
                ->disabled(),
            TextInput::make('payment_method')
                ->disabled(),
            TextInput::make('payment_status')
                ->disabled(),
            TextInput::make('total')
                ->numeric()
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->default('Guest')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paystack' => 'warning',
                        'card' => 'success',
                        'mock' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total Paid')
                    ->money('GHS', divideBy: 100)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'paystack' => 'Paystack',
                        'card' => 'Card',
                        'mock' => 'Mock',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Payment Details')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('order_number')
                                    ->disabled(),
                                TextInput::make('payment_method')
                                    ->disabled(),
                                TextInput::make('payment_status')
                                    ->disabled(),
                                DateTimePicker::make('created_at')
                                    ->label('Date')
                                    ->disabled(),
                            ]),
                        Section::make('Financial Breakdown')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->prefix('GHS')
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                                            ->disabled(),
                                        TextInput::make('delivery_fee')
                                            ->prefix('GHS')
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                                            ->disabled(),
                                        TextInput::make('service_fee')
                                            ->prefix('GHS')
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                                            ->disabled(),
                                        TextInput::make('tax')
                                            ->prefix('GHS')
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                                            ->disabled(),
                                        TextInput::make('discount')
                                            ->prefix('GHS')
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                                            ->disabled(),
                                        TextInput::make('total')
                                            ->prefix('GHS')
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0)
                                            ->disabled(),
                                    ]),
                            ]),
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
