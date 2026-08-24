<?php

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\City;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

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
        'price' => 1250, // $12.50
        'is_available' => true,
    ]);
});

test('customer can add items to cart session', function () {
    $this->post('/cart/add', [
        'menu_item_id' => $this->menuItem->id,
        'quantity' => 2,
        'notes' => 'No onions',
    ])->assertRedirect();

    $cart = session('cart');
    expect($cart)->not->toBeEmpty();

    $key = array_key_first($cart);
    expect($cart[$key]['quantity'])->toBe(2);
    expect($cart[$key]['notes'])->toBe('No onions');
});

test('customer can place order via checkout and is redirected to paystack', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/abcdefg',
                'access_code' => 'abcdefg',
                'reference' => 'SS-TESTORDER',
            ],
        ], 200),
    ]);

    // Put item in cart session
    $key = $this->menuItem->id.'_'.md5(serialize([]));
    session([
        'cart' => [
            $key => [
                'menu_item_id' => $this->menuItem->id,
                'quantity' => 2,
                'notes' => 'No onions',
                'customization' => [],
            ],
        ],
        'cart_restaurant_id' => $this->restaurant->id,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/checkout', [
            'type' => 'delivery',
            'delivery_address' => '123 Main St, Seattle',
            'special_instructions' => 'Ring bell',
        ]);

    $order = Order::first();
    expect($order)->not->toBeNull();
    expect($order->status)->toBe('received');
    expect($order->payment_status)->toBe('pending');
    expect($order->payment_method)->toBe('paystack');
    expect($order->total)->toBe($order->subtotal + $order->delivery_fee + $order->service_fee + $order->tax);

    $response->assertRedirect('https://checkout.paystack.com/abcdefg');
});

test('customer is redirected to order tracking after successful paystack payment callback', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => 'SS-TESTORDER',
                'amount' => 1800,
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $order = Order::create([
        'user_id' => $user->id,
        'restaurant_id' => $this->restaurant->id,
        'order_number' => 'SS-TESTORDER',
        'status' => 'received',
        'type' => 'delivery',
        'delivery_address' => '123 Main St, Seattle',
        'subtotal' => 1000,
        'delivery_fee' => 500,
        'service_fee' => 200,
        'tax' => 100,
        'total' => 1800,
        'payment_status' => 'pending',
        'payment_method' => 'paystack',
    ]);

    // Set cart in session to verify it is cleared
    session(['cart' => ['some_item']]);

    $response = $this->actingAs($user)
        ->get('/checkout/callback?reference=SS-TESTORDER');

    expect($order->fresh()->payment_status)->toBe('paid');
    expect(session('cart'))->toBeNull();

    $response->assertRedirect(route('orders.track', $order->id));
});

test('paystack webhook marks order as paid', function () {
    $user = User::factory()->create();

    $order = Order::create([
        'user_id' => $user->id,
        'restaurant_id' => $this->restaurant->id,
        'order_number' => 'SS-TESTORDER',
        'status' => 'received',
        'type' => 'delivery',
        'delivery_address' => '123 Main St, Seattle',
        'subtotal' => 1000,
        'delivery_fee' => 500,
        'service_fee' => 200,
        'tax' => 100,
        'total' => 1800,
        'payment_status' => 'pending',
        'payment_method' => 'paystack',
    ]);

    $payload = [
        'event' => 'charge.success',
        'data' => [
            'reference' => 'SS-TESTORDER',
            'amount' => 1800,
        ],
    ];

    $secret = 'test_secret_key';
    config(['services.paystack.secret_key' => $secret]);
    $signature = hash_hmac('sha512', json_encode($payload), $secret);

    $response = $this->postJson('/webhooks/paystack', $payload, [
        'X-Paystack-Signature' => $signature,
    ]);

    $response->assertOk();
    expect($order->fresh()->payment_status)->toBe('paid');
});

test('merchant can progress delivery order status via Filament actions', function () {
    // 1. Create a merchant user and log them in
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team, ['role' => 'owner']);
    $user->update(['current_team_id' => $team->id]);

    // 2. Associate our existing restaurant with this team to act as tenant
    $this->restaurant->update(['team_id' => $team->id]);

    // 3. Place an order (type = delivery)
    $order = Order::create([
        'user_id' => User::factory()->create()->id,
        'restaurant_id' => $this->restaurant->id,
        'order_number' => 'SS-TEST123',
        'status' => 'received',
        'type' => 'delivery',
        'subtotal' => 1000,
        'delivery_fee' => 500,
        'service_fee' => 200,
        'tax' => 100,
        'total' => 1800,
    ]);

    $this->actingAs($user);
    Filament::setTenant($this->restaurant);

    expect($order->fresh()->status)->toBe('received');

    // Accept the order -> status should become preparing
    Livewire::test(ListOrders::class)
        ->callTableAction('accept', $order);
    expect($order->fresh()->status)->toBe('preparing');

    // Mark as ready -> status should become out_for_delivery for delivery type
    Livewire::test(ListOrders::class)
        ->callTableAction('ready', $order);
    expect($order->fresh()->status)->toBe('out_for_delivery');

    // Complete the order -> status completed and payment status paid
    Livewire::test(ListOrders::class)
        ->callTableAction('complete', $order);
    expect($order->fresh()->status)->toBe('completed');
    expect($order->fresh()->payment_status)->toBe('paid');
});

test('merchant can progress pickup order status via Filament actions', function () {
    // 1. Create a merchant user and log them in
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team, ['role' => 'owner']);
    $user->update(['current_team_id' => $team->id]);

    // 2. Associate our existing restaurant with this team to act as tenant
    $this->restaurant->update(['team_id' => $team->id]);

    // 3. Place an order (type = pickup)
    $order = Order::create([
        'user_id' => User::factory()->create()->id,
        'restaurant_id' => $this->restaurant->id,
        'order_number' => 'SS-TEST456',
        'status' => 'received',
        'type' => 'pickup',
        'subtotal' => 1000,
        'delivery_fee' => 0,
        'service_fee' => 200,
        'tax' => 100,
        'total' => 1300,
    ]);

    $this->actingAs($user);
    Filament::setTenant($this->restaurant);

    expect($order->fresh()->status)->toBe('received');

    // Accept the order -> status should become preparing
    Livewire::test(ListOrders::class)
        ->callTableAction('accept', $order);
    expect($order->fresh()->status)->toBe('preparing');

    // Mark as ready -> status should become ready_for_pickup for pickup type
    Livewire::test(ListOrders::class)
        ->callTableAction('ready', $order);
    expect($order->fresh()->status)->toBe('ready_for_pickup');

    // Complete the order -> status completed and payment status paid
    Livewire::test(ListOrders::class)
        ->callTableAction('complete', $order);
    expect($order->fresh()->status)->toBe('completed');
    expect($order->fresh()->payment_status)->toBe('paid');
});
