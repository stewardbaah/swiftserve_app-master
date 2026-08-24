<?php

use App\Filament\Admin\Pages\Dashboard as AdminDashboard;
use App\Filament\Admin\Widgets\LatestOrdersWidget;
use App\Filament\Admin\Widgets\OrderStatusChart;
use App\Filament\Admin\Widgets\PlatformStatsOverview;
use App\Filament\Admin\Widgets\RevenueTrendChart;
use App\Filament\Pages\Dashboard as MerchantDashboard;
use App\Filament\Pages\Tenancy\RegisterRestaurant;
use App\Filament\Widgets\MerchantActiveOrdersWidget;
use App\Filament\Widgets\MerchantApprovalWidget;
use App\Filament\Widgets\MerchantMenuReadiness;
use App\Filament\Widgets\MerchantRevenueTrendChart;
use App\Filament\Widgets\MerchantStatsOverview;
use App\Models\City;
use App\Models\Complaint;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;
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

test('admin dashboard registers operational widgets', function () {
    $widgets = app(AdminDashboard::class)->getWidgets();

    expect($widgets)->toBe([
        PlatformStatsOverview::class,
        RevenueTrendChart::class,
        OrderStatusChart::class,
        LatestOrdersWidget::class,
    ]);
});

test('admin dashboard widgets render platform metrics and active orders', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create(['name' => 'Ada Customer']);
    $restaurant = Restaurant::factory()->create(['name' => 'Prime Bites', 'is_approved' => false]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'order_number' => 'SS-DASH01',
        'status' => 'preparing',
        'payment_status' => 'paid',
        'total' => 4250,
        'created_at' => now(),
    ]);

    Review::query()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'order_id' => $order->id,
        'rating' => 5,
        'comment' => 'Excellent.',
    ]);

    Complaint::query()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'order_id' => $order->id,
        'reason' => 'late_delivery',
        'details' => 'The order took longer than expected.',
        'status' => 'open',
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel('admin');

    Livewire::test(PlatformStatsOverview::class)
        ->assertSee('Today revenue')
        ->assertSee('Active orders')
        ->assertSee('Open complaints');

    Livewire::test(LatestOrdersWidget::class)
        ->assertSee('SS-DASH01')
        ->assertSee('Prime Bites')
        ->assertSee('Ada Customer');
});

test('merchant dashboard registers focused operational widgets', function () {
    $widgets = app(MerchantDashboard::class)->getWidgets();

    expect($widgets)->toBe([
        MerchantApprovalWidget::class,
        MerchantStatsOverview::class,
        MerchantRevenueTrendChart::class,
        MerchantMenuReadiness::class,
        MerchantActiveOrdersWidget::class,
    ]);
});

test('merchant dashboard widgets render tenant scoped operations', function () {
    $user = User::factory()->create();
    $customer = User::factory()->create(['name' => 'Merchant Customer']);
    $restaurant = Restaurant::factory()->create(['name' => 'Merchant Kitchen']);
    $otherRestaurant = Restaurant::factory()->create(['name' => 'Other Kitchen']);

    $user->teams()->attach($restaurant->team, ['role' => 'owner']);
    $user->update(['current_team_id' => $restaurant->team_id]);

    Order::factory()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'order_number' => 'SS-MERCH01',
        'status' => 'preparing',
        'payment_status' => 'paid',
        'total' => 3200,
        'created_at' => now(),
    ]);

    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'order_number' => 'SS-OTHER01',
        'status' => 'preparing',
        'payment_status' => 'paid',
        'total' => 9900,
        'created_at' => now(),
    ]);

    MenuItem::factory()->create([
        'restaurant_id' => $restaurant->id,
        'is_available' => true,
    ]);

    MenuItem::factory()->create([
        'restaurant_id' => $restaurant->id,
        'is_available' => false,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('merchant');
    Filament::setTenant($restaurant);

    Livewire::test(MerchantStatsOverview::class)
        ->assertSee('Today revenue')
        ->assertSee('Orders today')
        ->assertSee('Service modes');

    Livewire::test(MerchantMenuReadiness::class)
        ->assertSee('Available items')
        ->assertSee('Paused items');

    Livewire::test(MerchantActiveOrdersWidget::class)
        ->assertSee('SS-MERCH01')
        ->assertSee('Merchant Customer')
        ->assertDontSee('SS-OTHER01');
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
