<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->required(),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('cuisine_type')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('address')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                Textarea::make('operating_hours')
                    ->columnSpanFull(),
                TextInput::make('delivery_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('estimated_delivery_time')
                    ->required()
                    ->numeric()
                    ->default(30),
                TextInput::make('estimated_pickup_time')
                    ->required()
                    ->numeric()
                    ->default(15),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('rating_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                FileUpload::make('image_path')
                    ->image(),
                Toggle::make('offers_delivery')
                    ->required(),
                Toggle::make('offers_pickup')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('delivery_radius_km')
                    ->required()
                    ->numeric()
                    ->default(10),
                Toggle::make('is_approved')
                    ->required(),
            ]);
    }
}
