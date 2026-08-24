<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantProfileController extends Controller
{
    /**
     * Display the restaurant menu and profile details.
     */
    public function show(Restaurant $restaurant): Response
    {
        if (! $restaurant->is_approved) {
            abort(404);
        }

        $restaurant->load([
            'menuCategories' => fn ($query) => $query->orderBy('sort_order'),
            'menuCategories.menuItems' => fn ($query) => $query->where('is_available', true),
        ]);

        $isFavorited = auth()->check()
            ? auth()->user()->favoriteRestaurants()->where('restaurants.id', $restaurant->id)->exists()
            : false;

        return Inertia::render('Restaurants/Profile', [
            'restaurant' => $restaurant,
            'isFavorited' => $isFavorited,
        ]);
    }
}
