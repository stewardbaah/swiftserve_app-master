<?php

use App\Filament\Pages\Tenancy\RegisterRestaurant;
use App\Filament\Widgets\MerchantApprovalWidget;
use App\Models\City;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('guest is redirected to login when accessing admin panel', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

test('non-admin user is forbidden from accessing admin panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});

test('admin user can access admin panel', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertSuccessful();
});

test('tenant registration sets is_approved to false', function () {
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

    $this->assertDatabaseHas('restaurants', [
        'name' => 'New Accra Restaurant',
        'is_approved' => false,
    ]);
});

test('non-approved restaurant is hidden from directory and profile', function () {
    $city = City::create(['name' => 'Seattle', 'state' => 'WA', 'slug' => 'seattle', 'is_active' => true]);
    $team = Team::factory()->create();

    $restaurant = Restaurant::factory()->create([
        'team_id' => $team->id,
        'city_id' => $city->id,
        'name' => 'Hidden Restaurant',
        'slug' => 'hidden-restaurant',
        'is_active' => true,
        'is_approved' => false,
    ]);

    // Active city in session
    session(['active_city_slug' => 'seattle']);

    // Check directory
    $this->get(route('restaurants.directory'))
        ->assertOk()
        ->assertDontSee('Hidden Restaurant');

    // Check profile profile show route
    $this->get("/restaurants/{$restaurant->slug}")
        ->assertStatus(404);
});

test('approved restaurant is visible in directory and profile', function () {
    $city = City::create(['name' => 'Seattle', 'state' => 'WA', 'slug' => 'seattle', 'is_active' => true]);
    $team = Team::factory()->create();

    $restaurant = Restaurant::factory()->create([
        'team_id' => $team->id,
        'city_id' => $city->id,
        'name' => 'Visible Restaurant',
        'slug' => 'visible-restaurant',
        'is_active' => true,
        'is_approved' => true,
    ]);

    // Active city in session
    session(['active_city_slug' => 'seattle']);

    // Check directory
    $this->get(route('restaurants.directory'))
        ->assertOk()
        ->assertSee('Visible Restaurant');

    // Check profile
    $this->get("/restaurants/{$restaurant->slug}")
        ->assertOk()
        ->assertSee('Visible Restaurant');
});

test('merchant dashboard approval widget visibility', function () {
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
        'is_approved' => false,
    ]);

    $this->actingAs($user);

    // Run custom widget logic
    Filament::setTenant($restaurant);
    expect(MerchantApprovalWidget::canView())->toBeTrue();

    // Approve the restaurant and assert widget cannot be viewed
    $restaurant->update(['is_approved' => true]);
    expect(MerchantApprovalWidget::canView())->toBeFalse();
});
