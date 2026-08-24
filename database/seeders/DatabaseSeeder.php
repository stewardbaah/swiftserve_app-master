<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Cities
        $this->call(CitySeeder::class);
        $winneba = City::where('slug', 'winneba')->first();

        // 2. Create Admin/Merchant User
        $admin = User::factory()->create([
            'name' => 'Merchant Admin',
            'email' => 'admin@swiftserve.com',
            'password' => Hash::make('password'),
        ]);

        // 3. Create Team and link to Admin
        $team = Team::create([
            'name' => 'Tasty Burger Co.',
            'slug' => 'tasty-burger-co',
            'is_personal' => false,
        ]);
        $admin->teams()->attach($team, ['role' => 'owner']);
        $admin->update(['current_team_id' => $team->id]);

        // 4. Create Restaurant for Team
        $restaurant = Restaurant::create([
            'team_id' => $team->id,
            'city_id' => $winneba->id,
            'name' => 'Tasty Burger Co.',
            'slug' => 'tasty-burger-co',
            'cuisine_type' => 'American',
            'description' => 'Gourmet burgers and hand-cut fries in the heart of Winneba.',
            'address' => '456 Pine St, Winneba',
            'phone' => '(206) 555-0199',
            'delivery_fee' => 299, // $2.99
            'estimated_delivery_time' => 35,
            'estimated_pickup_time' => 15,
            'rating' => 4.8,
            'rating_count' => 1,
            'image_path' => 'restaurants/tasty_burger_cover.png',
            'is_active' => true,
        ]);

        // 5. Create Menu Categories
        $burgersCategory = MenuCategory::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Gourmet Burgers',
            'sort_order' => 1,
        ]);

        $sidesCategory = MenuCategory::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Sides & Drinks',
            'sort_order' => 2,
        ]);

        // 6. Create Menu Items
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $burgersCategory->id,
            'name' => 'The Classic Bacon Cheeseburger',
            'description' => '100% Angus beef, sharp cheddar, crispy smoked bacon, lettuce, tomato, and house burger sauce.',
            'price' => 1499, // $14.99
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $burgersCategory->id,
            'name' => 'Spicy Avocado Burger',
            'description' => 'Pepper jack cheese, fresh avocado, jalapeños, and spicy chipotle aioli.',
            'price' => 1599, // $15.99
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $sidesCategory->id,
            'name' => 'Truffle Parmesan Fries',
            'description' => 'Hand-cut russet potatoes tossed in white truffle oil, grated parmesan, and fresh parsley.',
            'price' => 699, // $6.99
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'menu_category_id' => $sidesCategory->id,
            'name' => 'Craft Soda',
            'description' => 'Naturally sweetened cane sugar soda. Choice of Cola, Lemon-Lime, or Root Beer.',
            'price' => 299, // $2.99
            'is_available' => true,
        ]);
    }
}
