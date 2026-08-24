<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Models\Restaurant;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EditRestaurantProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected string $view = 'filament.pages.edit-restaurant-profile';

    protected static ?string $navigationLabel = 'Restaurant Profile';

    protected static ?string $title = 'Restaurant Profile';

    public ?array $data = [];

    public function mount(): void
    {
        $restaurant = Filament::getTenant();

        if ($restaurant) {
            $this->form->fill(array_merge(
                $restaurant->toArray(),
                [
                    'deliveryCities' => $restaurant->deliveryCities()->pluck('cities.id')->toArray(),
                ]
            ));
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->model(Restaurant::class)
            ->schema([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('cuisine_type')
                            ->options([
                                'Ghanaian' => 'Ghanaian',
                                'Nigerian' => 'Nigerian',
                                'West African' => 'West African',
                                'American' => 'American',
                                'Chinese' => 'Chinese',
                                'Indian' => 'Indian',
                                'Italian' => 'Italian',
                                'French' => 'French',
                                'Japanese' => 'Japanese',
                                'Vietnamese' => 'Vietnamese',
                                'Other' => 'Other',
                            ])->preload()->searchable()
                            ->required(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Contact & Location')
                    ->schema([
                        Select::make('city_id')
                            ->relationship('city', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->label('Physical City')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->unique(table: 'cities', column: 'name')
                                    ->maxLength(255),
                                Select::make('state')
                                    ->options([
                                        'Ahafo' => 'Ahafo Region',
                                        'Ashanti' => 'Ashanti Region',
                                        'Bono' => 'Bono Region',
                                        'Bono East' => 'Bono East Region',
                                        'Central' => 'Central Region',
                                        'Eastern' => 'Eastern Region',
                                        'Greater Accra' => 'Greater Accra Region',
                                        'North East' => 'North East Region',
                                        'Northern' => 'Northern Region',
                                        'Oti' => 'Oti Region',
                                        'Savannah' => 'Savannah Region',
                                        'Upper East' => 'Upper East Region',
                                        'Upper West' => 'Upper West Region',
                                        'Volta' => 'Volta Region',
                                        'Western' => 'Western Region',
                                        'Western North' => 'Western North Region',
                                    ])
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $data['slug'] = Str::slug($data['name']);
                                $data['is_active'] = true;

                                return City::create($data)->id;
                            }),
                        TextInput::make('address')
                            ->id('restaurant-address')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Hidden::make('latitude')
                            ->id('restaurant-latitude'),
                        Hidden::make('longitude')
                            ->id('restaurant-longitude'),
                        ViewField::make('location_map')
                            ->view('filament.forms.components.location-picker')
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make('Delivery & Service Areas')
                    ->schema([
                        Select::make('deliveryCities')
                            ->relationship('deliveryCities', 'name')
                            ->multiple()
                            ->dehydrated(true)
                            ->preload()
                            ->searchable()
                            ->label('Delivery / Service Cities')
                            ->placeholder('Select cities where you operate or deliver to')
                            ->columnSpanFull()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->unique(table: 'cities', column: 'name')
                                    ->maxLength(255),
                                Select::make('state')
                                    ->options([
                                        'Ahafo' => 'Ahafo Region',
                                        'Ashanti' => 'Ashanti Region',
                                        'Bono' => 'Bono Region',
                                        'Bono East' => 'Bono East Region',
                                        'Central' => 'Central Region',
                                        'Eastern' => 'Eastern Region',
                                        'Greater Accra' => 'Greater Accra Region',
                                        'North East' => 'North East Region',
                                        'Northern' => 'Northern Region',
                                        'Oti' => 'Oti Region',
                                        'Savannah' => 'Savannah Region',
                                        'Upper East' => 'Upper East Region',
                                        'Upper West' => 'Upper West Region',
                                        'Volta' => 'Volta Region',
                                        'Western' => 'Western Region',
                                        'Western North' => 'Western North Region',
                                    ])->preload()->searchable()->required(),

                            ])
                            ->createOptionUsing(function (array $data) {
                                $data['slug'] = Str::slug($data['name']);
                                $data['is_active'] = true;

                                return City::create($data)->id;
                            }),
                    ]),
                Section::make('Operating & Delivery Settings')
                    ->schema([
                        TextInput::make('delivery_fee')
                            ->required()
                            ->numeric()
                            ->prefix('GHC')
                            ->dehydrateStateUsing(fn ($state) => (int) ($state * 100))
                            ->formatStateUsing(fn ($state) => $state / 100),
                        TextInput::make('estimated_delivery_time')
                            ->required()
                            ->numeric()
                            ->label('Estimated Delivery Time (minutes)'),
                        TextInput::make('estimated_pickup_time')
                            ->required()
                            ->numeric()
                            ->label('Estimated Pickup Time (minutes)'),
                        TextInput::make('delivery_radius_km')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(500)
                            ->default(10)
                            ->suffix('km')
                            ->label('Max Delivery Radius')
                            ->helperText('Orders outside this radius will not be accepted for delivery.'),
                        FileUpload::make('image_path')
                            ->image()
                            ->directory('restaurants')
                            ->visibility('public')
                            ->label('Restaurant Logo / Header'),
                        Toggle::make('offers_delivery')
                            ->required()
                            ->default(true),
                        Toggle::make('offers_pickup')
                            ->required()
                            ->default(true),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $restaurant = Filament::getTenant();

        if ($restaurant) {
            $state = $this->form->getState();
            $deliveryCities = $state['deliveryCities'] ?? [];
            unset($state['deliveryCities']);

            $restaurant->update($state);
            $restaurant->deliveryCities()->sync($deliveryCities);

            Notification::make()
                ->title('Profile updated successfully!')
                ->success()
                ->send();
        }
    }
}
