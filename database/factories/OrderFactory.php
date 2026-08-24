<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'restaurant_id' => Restaurant::factory(),
            'order_number' => 'SS-'.strtoupper(Str::random(8)),
            'status' => 'received',
            'type' => 'delivery',
            'subtotal' => 1500,
            'delivery_fee' => 300,
            'service_fee' => 200,
            'tax' => 120,
            'total' => 2120,
            'payment_status' => 'paid',
            'payment_method' => 'mock',
        ];
    }
}
