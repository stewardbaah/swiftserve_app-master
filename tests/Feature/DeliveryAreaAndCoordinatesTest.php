<?php

use App\Models\City;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->cityA = City::create(['name' => 'Accra', 'state' => 'Greater Accra', 'slug' => 'accra']);
    $this->cityB = City::create(['name' => 'Tema', 'state' => 'Greater Accra', 'slug' => 'tema']);
    $this->team = Team::factory()->create();

    // Restaurant A physically in Accra, delivers to Tema, coordinates set
    $this->restaurantA = Restaurant::factory()->create([
        'team_id' => $this->team->id,
        'city_id' => $this->cityA->id,
        'name' => 'Accra Delights',
        'slug' => 'accra-delights',
        'latitude' => 5.6037, // Accra coordinate
        'longitude' => -0.1870,
        'delivery_fee' => 500, // $5.00 fallback
        'is_active' => true,
    ]);
    $this->restaurantA->deliveryCities()->attach($this->cityB->id);

    // Restaurant B physically in Tema, only operates in Tema
    $this->restaurantB = Restaurant::factory()->create([
        'team_id' => Team::factory()->create()->id,
        'city_id' => $this->cityB->id,
        'name' => 'Tema Grills',
        'slug' => 'tema-grills',
        'latitude' => 5.6685, // Tema coordinate
        'longitude' => -0.0164,
        'delivery_fee' => 300,
        'is_active' => true,
    ]);
});

test('restaurants can have physical city, coordinates, and deliver to multiple cities', function () {
    expect($this->restaurantA->city_id)->toBe($this->cityA->id);
    expect($this->restaurantA->latitude)->toBe(5.6037);
    expect($this->restaurantA->longitude)->toBe(-0.1870);

    expect($this->restaurantA->deliveryCities)->toHaveCount(1);
    expect($this->restaurantA->deliveryCities->first()->id)->toBe($this->cityB->id);
});

test('landing page displays restaurants operating in the active city', function () {
    // With Accra active, should see Accra Delights
    session(['active_city_slug' => 'accra']);
    $response = $this->get(route('home'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('restaurants', 1)
        ->where('restaurants.0.slug', 'accra-delights')
    );

    // With Tema active, should see both Accra Delights (delivers to Tema) and Tema Grills
    session(['active_city_slug' => 'tema']);
    $response = $this->get(route('home'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('restaurants', 2)
    );
});

test('directory page filters restaurants by physical and delivery cities correlation', function () {
    // Accra directory has Accra Delights
    session(['active_city_slug' => 'accra']);
    $response = $this->get(route('restaurants.directory'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Restaurants/Directory')
        ->has('restaurants', 1)
        ->where('restaurants.0.slug', 'accra-delights')
    );

    // Tema directory has both Accra Delights (delivers here) and Tema Grills
    session(['active_city_slug' => 'tema']);
    $response = $this->get(route('restaurants.directory'));
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Restaurants/Directory')
        ->has('restaurants', 2)
    );
});

test('checkout calculates delivery fee based on Haversine distance', function () {
    $category = MenuCategory::create([
        'restaurant_id' => $this->restaurantA->id,
        'name' => 'Meals',
        'sort_order' => 1,
    ]);
    $menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurantA->id,
        'menu_category_id' => $category->id,
        'name' => 'Jollof',
        'price' => 1000,
        'is_available' => true,
    ]);

    // Put item in cart
    $key = $menuItem->id.'_'.md5(serialize([]));
    session([
        'cart' => [
            $key => [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
                'notes' => '',
                'customization' => [],
            ],
        ],
        'cart_restaurant_id' => $this->restaurantA->id,
    ]);

    $user = User::factory()->create();

    // 1. Without customer coordinates - uses restaurant fallback delivery fee ($5.00 / 500 cents)
    $response = $this->actingAs($user)->get('/checkout');
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Show')
        ->where('cart.delivery_fee', 500)
        ->where('cart.distance', null)
        ->where('cart.outside_delivery_area', null)
    );

    // 2. With coordinates: Accra Delights is at (5.6037, -0.1870)
    // Customer at Tema (5.6685, -0.0164) is ~20.26 km away.
    // Calculation: Base (200) + 20.26 * 150 = 200 + 3039 = 3239 cents ($32.39)
    // Accra Delights has delivery_radius_km = 10 (default), so 20 km is outside range.
    $response = $this->actingAs($user)->get('/checkout?latitude=5.6685&longitude=-0.0164');
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Show')
        ->where('cart.distance', 20.21)
        ->where('cart.delivery_fee', 3231)
        ->where('cart.outside_delivery_area', true)
    );
});

test('cart returns outside_delivery_area false when customer is within radius', function () {
    $this->restaurantA->update(['delivery_radius_km' => 30]); // 30 km radius — Tema at 20 km is within range

    $category = MenuCategory::create([
        'restaurant_id' => $this->restaurantA->id,
        'name' => 'Meals',
        'sort_order' => 1,
    ]);
    $menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurantA->id,
        'menu_category_id' => $category->id,
        'name' => 'Jollof',
        'price' => 1000,
        'is_available' => true,
    ]);

    $key = $menuItem->id.'_'.md5(serialize([]));
    session([
        'cart' => [
            $key => [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
                'notes' => '',
                'customization' => [],
            ],
        ],
        'cart_restaurant_id' => $this->restaurantA->id,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/checkout?latitude=5.6685&longitude=-0.0164');
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Show')
        ->where('cart.outside_delivery_area', false)
    );
});

test('placing a delivery order outside the radius is rejected with a validation error', function () {
    // radius is 10 km by default; Tema is ~20 km away — outside range
    $category = MenuCategory::create([
        'restaurant_id' => $this->restaurantA->id,
        'name' => 'Meals',
        'sort_order' => 1,
    ]);
    $menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurantA->id,
        'menu_category_id' => $category->id,
        'name' => 'Jollof',
        'price' => 1000,
        'is_available' => true,
    ]);

    $key = $menuItem->id.'_'.md5(serialize([]));
    session([
        'cart' => [
            $key => [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
                'notes' => '',
                'customization' => [],
            ],
        ],
        'cart_restaurant_id' => $this->restaurantA->id,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/checkout', [
        'type' => 'delivery',
        'delivery_address' => 'Tema, Ghana',
        'latitude' => 5.6685,
        'longitude' => -0.0164,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('delivery_address');
});

test('placing a delivery order outside the radius is allowed when type is pickup', function () {
    // Same scenario but pickup — should not be blocked by radius check
    $category = MenuCategory::create([
        'restaurant_id' => $this->restaurantA->id,
        'name' => 'Meals',
        'sort_order' => 1,
    ]);
    $menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurantA->id,
        'menu_category_id' => $category->id,
        'name' => 'Jollof',
        'price' => 1000,
        'is_available' => true,
    ]);

    $key = $menuItem->id.'_'.md5(serialize([]));
    session([
        'cart' => [
            $key => [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
                'notes' => '',
                'customization' => [],
            ],
        ],
        'cart_restaurant_id' => $this->restaurantA->id,
    ]);

    $user = User::factory()->create();

    // For pickup the radius check must not fire; Paystack will fail (no key) but the
    // order creation path should be reached, so we get a redirect (not a validation error).
    $response = $this->actingAs($user)->post('/checkout', [
        'type' => 'pickup',
        'delivery_address' => null,
        'latitude' => 5.6685,
        'longitude' => -0.0164,
    ]);

    $response->assertSessionDoesntHaveErrors('delivery_address');
});

test('cart returns outside_delivery_area null when restaurant has no coordinates', function () {
    $this->restaurantA->update(['latitude' => null, 'longitude' => null]);

    $category = MenuCategory::create([
        'restaurant_id' => $this->restaurantA->id,
        'name' => 'Meals',
        'sort_order' => 1,
    ]);
    $menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurantA->id,
        'menu_category_id' => $category->id,
        'name' => 'Jollof',
        'price' => 1000,
        'is_available' => true,
    ]);

    $key = $menuItem->id.'_'.md5(serialize([]));
    session([
        'cart' => [
            $key => [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
                'notes' => '',
                'customization' => [],
            ],
        ],
        'cart_restaurant_id' => $this->restaurantA->id,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/checkout?latitude=5.6685&longitude=-0.0164');
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Show')
        ->where('cart.outside_delivery_area', null)
    );
});
