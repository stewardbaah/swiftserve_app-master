<?php

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Models\City;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->city = City::create(['name' => 'Seattle', 'state' => 'WA', 'slug' => 'seattle']);

    $this->user->teams()->attach($this->team, ['role' => 'owner']);
    $this->user->update(['current_team_id' => $this->team->id]);

    $this->restaurant = Restaurant::factory()->create([
        'team_id' => $this->team->id,
        'city_id' => $this->city->id,
        'slug' => 'test-restaurant',
        'is_active' => true,
        'name' => 'Test Restaurant',
        'delivery_fee' => 500, // $5.00
    ]);

    $this->category = MenuCategory::create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'Burgers',
        'sort_order' => 1,
    ]);

    $this->menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurant->id,
        'menu_category_id' => $this->category->id,
        'name' => 'Burger',
        'price' => 1000, // $10.00
        'is_available' => true,
    ]);
});

test('can render order creation page', function () {
    $this->actingAs($this->user);
    Filament::setTenant($this->restaurant);

    $this->get("/merchant/{$this->restaurant->slug}/orders/create")
        ->assertSuccessful();
});

test('can create order using wizard', function () {
    $this->actingAs($this->user);
    Filament::setTenant($this->restaurant);

    Livewire::test(CreateOrder::class)
        ->fillForm([
            'type' => 'pickup',
            'orderItems' => [
                [
                    'menu_item_id' => $this->menuItem->id,
                    'quantity' => 2,
                    'price' => 10.00,
                    'notes' => 'No onions',
                ],
            ],
            'payment_method' => 'mock',
            'payment_status' => 'pending',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('orders', [
        'restaurant_id' => $this->restaurant->id,
        'type' => 'pickup',
        'subtotal' => 2000,
        'delivery_fee' => 0,
        'service_fee' => 200,
        'tax' => 300, // 15% of 2000
        'total' => 2500, // 2000 + 0 + 200 + 300
    ]);
});
