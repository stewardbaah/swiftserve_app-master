<?php

use App\Models\City;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->city = City::create(['name' => 'Accra', 'state' => 'Greater Accra', 'slug' => 'accra']);

    $this->user->teams()->attach($this->team, ['role' => 'owner']);
    $this->user->update(['current_team_id' => $this->team->id]);

    $this->restaurant = Restaurant::factory()->create([
        'team_id' => $this->team->id,
        'city_id' => $this->city->id,
        'slug' => 'test-restaurant',
        'is_active' => true,
        'name' => 'Test Restaurant',
        'delivery_fee' => 500,
    ]);
});

test('can render edit restaurant profile page', function () {
    $this->actingAs($this->user);
    Filament::setTenant($this->restaurant);

    $this->get("/merchant/{$this->restaurant->slug}/edit-restaurant-profile")
        ->assertSuccessful();
});
