<?php

use App\Filament\Pages\Tenancy\RegisterRestaurant;
use App\Models\City;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('guest is redirected to login when accessing merchant panel', function () {
    $this->get('/merchant')
        ->assertRedirect('/merchant/login');
});

test('user is redirected to tenant registration if they have no restaurant', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/merchant')
        ->assertRedirect(route('filament.merchant.tenant.registration'));
});

test('user can access merchant panel if they belong to a team with a restaurant', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $city = City::create(['name' => 'Seattle', 'state' => 'WA', 'slug' => 'seattle']);

    // Associate user with team
    $user->teams()->attach($team, ['role' => 'owner']);
    $user->update(['current_team_id' => $team->id]);

    $restaurant = Restaurant::factory()->create([
        'team_id' => $team->id,
        'city_id' => $city->id,
        'slug' => 'test-restaurant',
        'is_active' => true,
        'name' => 'Test Restaurant',
    ]);

    $this->actingAs($user)
        ->get("/merchant/{$restaurant->slug}")
        ->assertSuccessful();
});

test('can render tenant registration page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('filament.merchant.tenant.registration'))
        ->assertSuccessful();
});

test('tenant registration redirects to dashboard if user already has a restaurant', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $city = City::create(['name' => 'Seattle', 'state' => 'WA', 'slug' => 'seattle']);

    $user->teams()->attach($team, ['role' => 'owner']);
    $user->update(['current_team_id' => $team->id]);

    $restaurant = Restaurant::factory()->create([
        'team_id' => $team->id,
        'city_id' => $city->id,
        'slug' => 'test-restaurant',
        'is_active' => true,
        'name' => 'Test Restaurant',
    ]);

    $this->actingAs($user)
        ->get(route('filament.merchant.tenant.registration'))
        ->assertRedirect(route('filament.merchant.pages.dashboard', ['tenant' => $restaurant->slug]));
});

test('can register a new restaurant as a tenant', function () {
    $user = User::factory()->create();
    $city = City::create(['name' => 'Accra', 'state' => 'Greater Accra', 'slug' => 'accra']);

    $this->actingAs($user);

    Livewire::test(RegisterRestaurant::class)
        ->fillForm([
            'name' => 'New Accra Restaurant',
            'cuisine_type' => 'Ghanaian',
            'city_id' => $city->id,
            'address' => '123 Ring Road',
            'phone' => '0241234567',
            'delivery_fee' => 7.50,
            'estimated_delivery_time' => 45,
            'estimated_pickup_time' => 20,
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    // Verify team and restaurant are created and associated
    $this->assertDatabaseHas('teams', [
        'name' => 'New Accra Restaurant',
    ]);

    $team = Team::where('name', 'New Accra Restaurant')->first();
    expect($team)->not->toBeNull();

    $this->assertDatabaseHas('restaurants', [
        'team_id' => $team->id,
        'city_id' => $city->id,
        'name' => 'New Accra Restaurant',
        'cuisine_type' => 'Ghanaian',
        'address' => '123 Ring Road',
        'delivery_fee' => 750, // 7.50 dehydrated to cents
    ]);

    expect($user->belongsToTeam($team))->toBeTrue();
});
