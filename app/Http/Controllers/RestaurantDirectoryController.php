<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantDirectoryController extends Controller
{
    /**
     * Display a listing of the restaurants in the selected city.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $citySlug = session('active_city_slug');

        if (! $citySlug) {
            return redirect()->route('home');
        }

        $city = City::where('slug', $citySlug)->where('is_active', true)->first();

        if (! $city) {
            return redirect()->route('home');
        }

        $query = Restaurant::approved()->where('is_active', true)
            ->where(function ($q) use ($city) {
                $q->where('city_id', $city->id)
                    ->orWhereHas('deliveryCities', function ($q2) use ($city) {
                        $q2->where('cities.id', $city->id);
                    });
            });

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('cuisine_type', 'like', "%{$search}%")
            );
        }

        if ($request->filled('cuisine')) {
            $query->where('cuisine_type', $request->input('cuisine'));
        }

        if ($request->boolean('offers_delivery')) {
            $query->where('offers_delivery', true);
        }

        if ($request->boolean('offers_pickup')) {
            $query->where('offers_pickup', true);
        }

        // Apply sorting
        $sort = $request->input('sort', 'rating');
        if ($sort === 'rating') {
            $query->orderBy('rating', 'desc');
        } elseif ($sort === 'delivery_fee') {
            $query->orderBy('delivery_fee', 'asc');
        } elseif ($sort === 'time') {
            $query->orderBy('estimated_delivery_time', 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $restaurants = $query->get();

        $cuisineTypes = Restaurant::approved()->where('is_active', true)
            ->where(function ($q) use ($city) {
                $q->where('city_id', $city->id)
                    ->orWhereHas('deliveryCities', function ($q2) use ($city) {
                        $q2->where('cities.id', $city->id);
                    });
            })
            ->distinct()
            ->pluck('cuisine_type');

        return Inertia::render('Restaurants/Directory', [
            'restaurants' => $restaurants,
            'cuisineTypes' => $cuisineTypes,
            'activeCity' => $city,
            'filters' => $request->only(['search', 'cuisine', 'offers_delivery', 'offers_pickup', 'sort']),
            'favoriteIds' => auth()->check() ? auth()->user()->favoriteRestaurants()->pluck('restaurants.id')->toArray() : [],
        ]);
    }
}
