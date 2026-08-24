<?php

use App\Http\Controllers\CartController;
use App\Models\City;
use App\Models\Complaint;
use App\Models\Discount;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Tax;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->city = City::create(['name' => 'Seattle', 'state' => 'WA', 'slug' => 'seattle']);
    $this->team = Team::factory()->create();
    $this->restaurant = Restaurant::factory()->create([
        'team_id' => $this->team->id,
        'city_id' => $this->city->id,
        'slug' => 'seattle-eats',
        'is_active' => true,
    ]);
    $this->category = MenuCategory::create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'Burgers',
        'sort_order' => 1,
    ]);
    $this->menuItem = MenuItem::create([
        'restaurant_id' => $this->restaurant->id,
        'menu_category_id' => $this->category->id,
        'name' => 'Classic Burger',
        'price' => 1000, // GHS 10.00
        'is_available' => true,
    ]);
});

test('cart dynamic tax rate is calculated correctly based on configured taxes', function () {
    // 1. Configure dynamic taxes for restaurant (10% and 5% active taxes)
    Tax::create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'VAT',
        'rate' => 10.00,
        'is_active' => true,
    ]);
    Tax::create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'Service Tax',
        'rate' => 5.00,
        'is_active' => true,
    ]);
    // Inactive tax should not count
    Tax::create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'Luxury Tax',
        'rate' => 2.50,
        'is_active' => false,
    ]);

    // 2. Add classic burger (qty 2 -> subtotal GHS 20.00) to cart
    $this->post('/cart/add', [
        'menu_item_id' => $this->menuItem->id,
        'quantity' => 2,
    ])->assertRedirect();

    $cartDetails = CartController::getCartDetails();
    expect($cartDetails['subtotal'])->toBe(2000);
    // Dynamic tax rate = 15%. 15% of GHS 20.00 = GHS 3.00 (300 cents)
    expect($cartDetails['tax'])->toBe(300);
    expect($cartDetails['tax_rate'])->toBe(15.0);
});

test('cart dynamic fixed and percentage discounts apply correctly', function () {
    // 1. Configure dynamic discounts for restaurant
    $fixedDiscount = Discount::create([
        'restaurant_id' => $this->restaurant->id,
        'code' => 'SAVE5',
        'type' => 'fixed',
        'value' => 500, // GHS 5.00
        'min_order_amount' => 1500, // GHS 15.00 min order
        'is_active' => true,
    ]);

    $pctDiscount = Discount::create([
        'restaurant_id' => $this->restaurant->id,
        'code' => 'SAVE10PCT',
        'type' => 'percentage',
        'value' => 10, // 10%
        'min_order_amount' => 1000, // GHS 10.00 min order
        'is_active' => true,
    ]);

    // Add classic burger (qty 2 -> subtotal GHS 20.00) to cart
    $this->post('/cart/add', [
        'menu_item_id' => $this->menuItem->id,
        'quantity' => 2,
    ])->assertRedirect();

    // 2. Apply fixed discount code SAVE5
    session(['cart_restaurant_id' => $this->restaurant->id]);
    $this->post('/checkout/promo', ['promo_code' => 'SAVE5'])->assertRedirect();
    $cartDetails = CartController::getCartDetails();
    expect($cartDetails['discount'])->toBe(500);

    // 3. Apply percentage discount code SAVE10PCT
    $this->post('/checkout/promo', ['promo_code' => 'SAVE10PCT'])->assertRedirect();
    $cartDetails = CartController::getCartDetails();
    // 10% of GHS 20.00 = GHS 2.00 (200 cents)
    expect($cartDetails['discount'])->toBe(200);
});

test('customer can submit a complaint for their order and view it on tracking page', function () {
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'restaurant_id' => $this->restaurant->id,
        'order_number' => 'SS-COMPLAIN1',
        'status' => 'completed',
        'type' => 'pickup',
        'subtotal' => 1000,
        'delivery_fee' => 0,
        'tax' => 80,
        'total' => 1280,
        'payment_status' => 'paid',
        'payment_method' => 'mock',
    ]);

    // 1. Submit complaint through the controller endpoint
    $this->actingAs($user)
        ->post("/orders/{$order->id}/complaint", [
            'reason' => 'Missing Items',
            'details' => 'The fries were missing from my package.',
        ])->assertRedirect();

    // 2. Verify complaint was written to the database
    $complaint = Complaint::where('order_id', $order->id)->first();
    expect($complaint)->not->toBeNull();
    expect($complaint->reason)->toBe('Missing Items');
    expect($complaint->status)->toBe('pending');

    // 3. Verify complaint is loaded and displayed on the tracking screen
    $response = $this->actingAs($user)->get("/orders/{$order->id}/track");
    $response->assertStatus(200);
    $orderData = $response->original->getData()['page']['props']['order'];
    expect($orderData['complaint'])->not->toBeNull();
    expect($orderData['complaint']['reason'])->toBe('Missing Items');
});
