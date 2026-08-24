<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Info')
                    ->schema([
                        TextInput::make('order_number')
                            ->disabled(),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Customer')
                            ->placeholder('Guest')
                            ->disabled(),
                        TextInput::make('type')
                            ->disabled(),
                        TextInput::make('status')
                            ->disabled(),
                        DateTimePicker::make('estimated_ready_time')
                            ->disabled(),
                        Textarea::make('special_instructions')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(),
                        Textarea::make('delivery_address')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(),
                    ])->columns(2),
                Section::make('Payment & Totals')
                    ->schema([
                        TextInput::make('payment_status')
                            ->disabled(),
                        TextInput::make('payment_method')
                            ->disabled(),
                        TextInput::make('promo_code')
                            ->disabled(),
                        TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('GHS')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->disabled(),
                        TextInput::make('delivery_fee')
                            ->numeric()
                            ->prefix('GHS')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->disabled(),
                        TextInput::make('service_fee')
                            ->numeric()
                            ->prefix('GHS')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->disabled(),
                        TextInput::make('tax')
                            ->numeric()
                            ->prefix('GHS')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->disabled(),
                        TextInput::make('discount')
                            ->numeric()
                            ->prefix('GHS')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->disabled(),
                        TextInput::make('total')
                            ->numeric()
                            ->prefix('GHS')
                            ->formatStateUsing(fn ($state) => $state / 100)
                            ->disabled(),
                    ])->columns(2),
                Section::make('Order Items')
                    ->schema([
                        Repeater::make('orderItems')
                            ->relationship('orderItems')
                            ->schema([
                                Select::make('menu_item_id')
                                    ->relationship('menuItem', 'name')
                                    ->label('Item')->searchable()->columnSpan(2),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('price')
                                    ->numeric()
                                    ->prefix('GHS')
                                    ->formatStateUsing(fn ($state) => $state / 100)
                                    ->disabled(),
                                TextInput::make('notes')
                                    ->disabled(),
                            ])->columns(5)
                            ->disabled()
                            ->dehydrated(false)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ])->columnSpanFull(),
            ]);
    }
}
