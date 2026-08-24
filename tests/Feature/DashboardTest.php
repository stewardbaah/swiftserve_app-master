<?php

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SavedAddress;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard and see active/past orders, stats, and addresses', function () {
    $user = User::factory()->create();

    // Create an active order
    $activeOrder = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'preparing',
    ]);

    // Create a completed order
    $pastOrder = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'payment_status' => 'paid',
        'total' => 2500,
    ]);

    // Create a saved address
    $address = SavedAddress::create([
        'user_id' => $user->id,
        'label' => 'Home',
        'address_line1' => '123 Main St',
        'city' => 'Accra',
        'state' => 'Greater Accra',
        'postal_code' => '00233',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('activeOrders', 1)
        ->where('activeOrders.0.id', $activeOrder->id)
        ->has('pastOrders', 1)
        ->where('pastOrders.0.id', $pastOrder->id)
        ->has('savedAddresses', 1)
        ->where('savedAddresses.0.id', $address->id)
        ->where('stats.totalOrdersCount', 1)
        ->where('stats.totalSpent', 2500)
    );
});

test('authenticated users can store a saved address', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('addresses.store'), [
            'label' => 'Work',
            'address_line1' => '456 Business Rd',
            'city' => 'Kumasi',
            'state' => 'Ashanti',
            'postal_code' => '00234',
            'latitude' => 6.6885,
            'longitude' => -1.6244,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('saved_addresses', [
        'user_id' => $user->id,
        'label' => 'Work',
        'address_line1' => '456 Business Rd',
        'latitude' => 6.6885,
        'longitude' => -1.6244,
    ]);
});

test('authenticated users can update their saved address', function () {
    $user = User::factory()->create();
    $address = SavedAddress::create([
        'user_id' => $user->id,
        'label' => 'Home',
        'address_line1' => '123 Main St',
        'city' => 'Accra',
        'state' => 'Greater Accra',
        'postal_code' => '00233',
        'latitude' => 5.6037,
        'longitude' => -0.1870,
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('addresses.update', $address->id), [
            'label' => 'Home Sweet Home',
            'address_line1' => '123 Main St Suite B',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'postal_code' => '00233',
            'latitude' => 5.6100,
            'longitude' => -0.1900,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('saved_addresses', [
        'id' => $address->id,
        'label' => 'Home Sweet Home',
        'address_line1' => '123 Main St Suite B',
        'latitude' => 5.6100,
        'longitude' => -0.1900,
    ]);
});

test('authenticated users cannot update others saved address', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $address = SavedAddress::create([
        'user_id' => $user2->id,
        'label' => 'Home',
        'address_line1' => '123 Main St',
        'city' => 'Accra',
        'state' => 'Greater Accra',
        'postal_code' => '00233',
    ]);

    $response = $this
        ->actingAs($user1)
        ->put(route('addresses.update', $address->id), [
            'label' => 'Hack Attempt',
            'address_line1' => '666 Hack St',
            'city' => 'Accra',
            'state' => 'Greater Accra',
            'postal_code' => '00233',
        ]);

    $response->assertForbidden();
});

test('authenticated users can delete their saved address', function () {
    $user = User::factory()->create();
    $address = SavedAddress::create([
        'user_id' => $user->id,
        'label' => 'Home',
        'address_line1' => '123 Main St',
        'city' => 'Accra',
        'state' => 'Greater Accra',
        'postal_code' => '00233',
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('addresses.destroy', $address->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('saved_addresses', [
        'id' => $address->id,
    ]);
});

test('authenticated users cannot delete others saved address', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $address = SavedAddress::create([
        'user_id' => $user2->id,
        'label' => 'Home',
        'address_line1' => '123 Main St',
        'city' => 'Accra',
        'state' => 'Greater Accra',
        'postal_code' => '00233',
    ]);

    $response = $this
        ->actingAs($user1)
        ->delete(route('addresses.destroy', $address->id));

    $response->assertForbidden();
});

test('authenticated users can reorder a past order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
    ]);

    $menuItem = MenuItem::factory()->create([
        'price' => 1200,
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'menu_item_id' => $menuItem->id,
        'quantity' => 2,
        'price' => 1200,
        'customization' => ['size' => 'large'],
        'notes' => 'Extra spicy',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('orders.reorder', $order->id));

    $response->assertRedirect(route('checkout.show'));

    $cart = session('cart');
    $this->assertCount(1, $cart);

    $cartItem = array_values($cart)[0];
    $this->assertEquals($menuItem->id, $cartItem['menu_item_id']);
    $this->assertEquals(2, $cartItem['quantity']);
    $this->assertEquals('Extra spicy', $cartItem['notes']);
    $this->assertEquals(['size' => 'large'], $cartItem['customization']);
    $this->assertEquals($order->restaurant_id, session('cart_restaurant_id'));
});

test('authenticated users can visit the dashboard/orders page', function () {
    $user = User::factory()->create();

    $activeOrder = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'preparing',
    ]);

    $pastOrder = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'payment_status' => 'paid',
        'total' => 2500,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard.orders'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard/Orders')
        ->has('activeOrders', 1)
        ->where('activeOrders.0.id', $activeOrder->id)
        ->has('pastOrders.data', 1)
        ->where('pastOrders.data.0.id', $pastOrder->id)
    );
});

test('authenticated users can visit the dashboard/payments page', function () {
    $user = User::factory()->create();

    $order1 = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'payment_status' => 'paid',
        'total' => 2000,
    ]);

    $order2 = Order::factory()->create([
        'user_id' => $user->id,
        'status' => 'completed',
        'payment_status' => 'paid',
        'total' => 3000,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard.payments'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard/Payments')
        ->has('transactions.data', 2)
        ->where('stats.totalOrdersCount', 2)
        ->where('stats.totalSpent', 5000)
        ->where('stats.averageSpent', 2500)
    );
});

test('authenticated users can visit the dashboard/addresses page', function () {
    $user = User::factory()->create();

    $address = SavedAddress::create([
        'user_id' => $user->id,
        'label' => 'Office',
        'address_line1' => '789 Corporate Blvd',
        'city' => 'Kumasi',
        'state' => 'Ashanti',
        'postal_code' => '00234',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard.addresses'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard/Addresses')
        ->has('savedAddresses', 1)
        ->where('savedAddresses.0.id', $address->id)
    );
});
