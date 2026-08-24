<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->id;

        return parent::getEloquentQuery()
            ->whereHas('orders', function ($query) use ($tenantId) {
                $query->where('restaurant_id', $tenantId);
            })
            ->withCount([
                'orders' => function ($query) use ($tenantId) {
                    $query->where('restaurant_id', $tenantId);
                },
            ])
            ->withSum([
                'orders' => function ($query) use ($tenantId) {
                    $query->where('restaurant_id', $tenantId)->where('status', 'completed');
                },
            ], 'total')
            ->withMax([
                'orders' => function ($query) use ($tenantId) {
                    $query->where('restaurant_id', $tenantId);
                },
            ], 'created_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->disabled(),
            TextInput::make('email')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Orders Placed')
                    ->sortable(),
                TextColumn::make('orders_sum_total')
                    ->label('Total Spent')
                    ->money('GHS', divideBy: 100)
                    ->sortable(),
                TextColumn::make('orders_max_created_at')
                    ->label('Last Order')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()->slideOver(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
        ];
    }
}
