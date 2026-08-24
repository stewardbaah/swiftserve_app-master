<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required(),
                TextInput::make('order_number')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('received'),
                TextInput::make('type')
                    ->required(),
                Textarea::make('delivery_address')
                    ->columnSpanFull(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('delivery_fee')
                    ->required()
                    ->numeric(),
                TextInput::make('service_fee')
                    ->required()
                    ->numeric()
                    ->default(200),
                TextInput::make('tax')
                    ->required()
                    ->numeric(),
                TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                Textarea::make('special_instructions')
                    ->columnSpanFull(),
                DateTimePicker::make('estimated_ready_time'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('pending'),
                TextInput::make('payment_method')
                    ->required()
                    ->default('mock'),
                TextInput::make('promo_code'),
            ]);
    }
}
