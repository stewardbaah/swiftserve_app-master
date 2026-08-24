<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuCategory;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('menu_category_id')
                    ->relationship(
                        name: 'menuCategory',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('restaurant_id', Filament::getTenant()?->id)
                    )
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(2),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $data['restaurant_id'] = Filament::getTenant()?->id;
                        $data['sort_order'] = 0;

                        return MenuCategory::create($data)->id;
                    })
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('GHS')
                    ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                    ->formatStateUsing(fn ($state) => $state / 100),
                FileUpload::make('image_path')
                    ->image()
                    ->directory('menu-items')
                    ->visibility('public'),
                Toggle::make('is_available')
                    ->required()
                    ->default(true),
            ]);
    }
}
