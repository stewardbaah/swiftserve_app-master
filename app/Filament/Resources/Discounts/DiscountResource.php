<?php

namespace App\Filament\Resources\Discounts;

use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Models\Discount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Discounts';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                ->placeholder('WELCOME10'),
            Select::make('type')
                ->options([
                    'fixed' => 'Fixed Amount (GHS)',
                    'percentage' => 'Percentage (%)',
                ])
                ->required()
                ->default('fixed')
                ->live(),
            TextInput::make('value')
                ->numeric()
                ->required()
                ->dehydrateStateUsing(fn ($state, $get) => $get('type') === 'fixed' ? (int) ($state * 100) : (int) $state)
                ->formatStateUsing(fn ($state, $get) => $get('type') === 'fixed' && $state !== null ? $state / 100 : $state)
                ->prefix(fn ($get) => $get('type') === 'fixed' ? 'GHS' : null)
                ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : null)
                ->live(),
            TextInput::make('min_order_amount')
                ->label('Minimum Order Amount')
                ->numeric()
                ->required()
                ->default(0)
                ->prefix('GHS')
                ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : 0),
            DateTimePicker::make('expires_at')
                ->label('Expires At')
                ->nullable(),
            Toggle::make('is_active')
                ->required()
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'fixed' ? 'info' : 'warning')
                    ->sortable(),
                TextColumn::make('value')
                    ->state(fn (Discount $record) => $record->type === 'fixed' ? 'GHS '.number_format($record->value / 100, 2) : $record->value.'%')
                    ->sortable(),
                TextColumn::make('min_order_amount')
                    ->money('GHS', divideBy: 100)
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
        ];
    }
}
