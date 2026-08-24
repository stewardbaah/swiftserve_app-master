<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Restaurant;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'city_id' => City::factory(),
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(),
            'cuisine_type' => $this->faker->randomElement(['Italian', 'Mexican', 'Indian', 'American', 'Sushi']),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'delivery_fee' => $this->faker->numberBetween(0, 500),
            'estimated_delivery_time' => $this->faker->numberBetween(15, 60),
            'estimated_pickup_time' => $this->faker->numberBetween(10, 45),
            'rating' => $this->faker->randomFloat(2, 3, 5),
            'rating_count' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'is_approved' => true,
        ];
    }
}
