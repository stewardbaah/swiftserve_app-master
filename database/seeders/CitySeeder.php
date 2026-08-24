<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            ['name' => 'Winneba', 'state' => 'Central', 'slug' => 'winneba', 'is_active' => true],
            ['name' => 'Accra', 'state' => 'Greater Accra', 'slug' => 'accra', 'is_active' => true],
            ['name' => 'Kumasi', 'state' => 'Ashanti', 'slug' => 'kumasi', 'is_active' => true],
            ['name' => 'Tamale', 'state' => 'Northern', 'slug' => 'tamale', 'is_active' => true],
            ['name' => 'Takoradi', 'state' => 'Western', 'slug' => 'takoradi', 'is_active' => true],
            ['name' => 'Tema', 'state' => 'Greater Accra', 'slug' => 'tema', 'is_active' => true],
            ['name' => 'Cape Coast', 'state' => 'Central', 'slug' => 'cape-coast', 'is_active' => false], // Inactive for testing filtering
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
