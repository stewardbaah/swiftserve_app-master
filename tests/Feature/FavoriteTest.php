<?php

use App\Models\Restaurant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page when toggling favorites', function () {
    $restaurant = Restaurant::factory()->create();

    $response = $this->post(route('restaurants.favorite', $restaurant->id));

    $response->assertRedirect(route('login'));
});

test('authenticated users can toggle a restaurant to favorites and back', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->create();

    // 1. Favorite
    $response = $this
        ->actingAs($user)
        ->post(route('restaurants.favorite', $restaurant->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('favorites', [
        'user_id' => $user->id,
        'restaurant_id' => $restaurant->id,
    ]);

    // 2. Unfavorite
    $response = $this
        ->actingAs($user)
        ->post(route('restaurants.favorite', $restaurant->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('favorites', [
        'user_id' => $user->id,
        'restaurant_id' => $restaurant->id,
    ]);
});

test('authenticated users can visit dashboard/favorites page and see list of favorites', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->create([
        'is_active' => true,
    ]);

    // Favorite it first
    $user->favoriteRestaurants()->attach($restaurant->id);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard.favorites'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard/Favorites')
        ->has('favorites', 1)
        ->where('favorites.0.id', $restaurant->id)
    );
});
