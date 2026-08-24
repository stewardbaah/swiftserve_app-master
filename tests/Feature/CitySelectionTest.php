<?php

use App\Models\City;
use Inertia\Testing\AssertableInertia as Assert;

test('landing page displays active cities', function () {
    $activeCity = City::create([
        'name' => 'Test Seattle',
        'state' => 'WA',
        'slug' => 'test-seattle',
        'is_active' => true,
    ]);

    $inactiveCity = City::create([
        'name' => 'Test Miami',
        'state' => 'FL',
        'slug' => 'test-miami',
        'is_active' => false,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('cities')
        ->where('cities.0.slug', 'test-seattle')
        ->missing('cities.1') // Assuming only one active city in test db, or at least inactive is not there
    );
});

test('user can select a city which sets the session', function () {
    $city = City::create([
        'name' => 'Test Austin',
        'state' => 'TX',
        'slug' => 'test-austin',
        'is_active' => true,
    ]);

    $response = $this->post(route('cities.select'), [
        'city_slug' => 'test-austin',
    ]);

    $response->assertRedirect();
    $this->assertEquals('test-austin', session('active_city_slug'));
});

test('invalid city selection fails validation', function () {
    $response = $this->post(route('cities.select'), [
        'city_slug' => 'non-existent-city',
    ]);

    $response->assertSessionHasErrors(['city_slug']);
});

test('user can auto-detect city', function () {
    $city = City::create([
        'name' => 'Seattle',
        'state' => 'WA',
        'slug' => 'seattle',
        'is_active' => true,
    ]);

    $response = $this->post(route('cities.detect'));

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'city' => [
            'slug' => 'seattle',
        ],
    ]);

    $this->assertEquals('seattle', session('active_city_slug'));
});
