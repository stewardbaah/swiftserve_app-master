<?php

namespace App\Filament\Pages\Tenancy;

use App\Actions\Teams\CreateTeam;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegisterRestaurant extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Restaurant';
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && $user->teams()->whereHas('restaurant')->exists()) {
            $restaurant = $user->teams()->whereHas('restaurant')->first()->restaurant;

            redirect(Filament::getUrl($restaurant));

            return;
        }

        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->label('Physical City'),
                TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
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
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = auth()->user();

        // 1. Create the Team using the restaurant's name
        $team = (new CreateTeam)->handle($user, $data['name']);

        // 2. Create the Restaurant associated with the Team
        $restaurant = Restaurant::create([
            'team_id' => $team->id,
            'city_id' => $data['city_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'cuisine_type' => $data['cuisine_type'],
            'address' => $data['address'],
            'phone' => $data['phone'] ?? null,
            'delivery_fee' => (int) $data['delivery_fee'],
            'estimated_delivery_time' => (int) $data['estimated_delivery_time'],
            'estimated_pickup_time' => (int) $data['estimated_pickup_time'],
            'rating' => 0.0,
            'rating_count' => 0,
            'is_active' => true,
            'is_approved' => false,
        ]);

        return $restaurant;
    }
}
