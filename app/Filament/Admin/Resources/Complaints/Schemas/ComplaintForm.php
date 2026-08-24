<?php

namespace App\Filament\Admin\Resources\Complaints\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                TextInput::make('reason')
                    ->required(),
                Textarea::make('details')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('resolution_comment')
                    ->columnSpanFull(),
            ]);
    }
}
