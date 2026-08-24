<?php

namespace App\Filament\Resources\Complaints;

use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Models\Complaint;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Complaints';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order.order_number')
                ->label('Order Number')
                ->disabled(),
            TextInput::make('user.name')
                ->label('Customer')
                ->placeholder('Guest')
                ->disabled(),
            TextInput::make('reason')
                ->disabled(),
            Textarea::make('details')
                ->disabled(),
            TextInput::make('status')
                ->disabled(),
            Textarea::make('resolution_comment')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->default('Guest')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->sortable(),
                TextColumn::make('details')
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'resolved' => 'success',
                        'dismissed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),
            ])
            ->actions([
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Complaint $record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('resolution_comment')
                            ->label('Resolution Details')
                            ->placeholder('Describe what action was taken (e.g. Refund issued, item re-sent)...')
                            ->required(),
                    ])
                    ->action(function (Complaint $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'resolution_comment' => $data['resolution_comment'],
                        ]);
                        Notification::make()
                            ->title('Complaint marked as resolved')
                            ->success()
                            ->send();
                    }),
                Action::make('dismiss')
                    ->label('Dismiss')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Complaint $record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('resolution_comment')
                            ->label('Dismissal Reason')
                            ->placeholder('Describe why this complaint is dismissed...')
                            ->required(),
                    ])
                    ->action(function (Complaint $record, array $data) {
                        $record->update([
                            'status' => 'dismissed',
                            'resolution_comment' => $data['resolution_comment'],
                        ]);
                        Notification::make()
                            ->title('Complaint dismissed')
                            ->danger()
                            ->send();
                    }),
                ViewAction::make()->slideOver(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaints::route('/'),
        ];
    }
}
