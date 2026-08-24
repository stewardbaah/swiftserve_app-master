<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    /**
     * Display the landing page with active cities.
     */
    public function index(): Response
    {
        $cities = City::where('is_active', true)
            ->orderBy('name')
            ->get();

        $activeCity = null;
        $activeCitySlug = session('active_city_slug');
        if ($activeCitySlug) {
            $activeCity = City::where('slug', $activeCitySlug)->where('is_active', true)->first();
        }

        $restaurantsQuery = Restaurant::approved()->where('is_active', true);
        if ($activeCity) {
            $restaurantsQuery->where(function ($q) use ($activeCity) {
                $q->where('city_id', $activeCity->id)
                    ->orWhereHas('deliveryCities', function ($q2) use ($activeCity) {
                        $q2->where('cities.id', $activeCity->id);
                    });
            });
        }

        // Return up to 4 restaurants for the landing page grid
        $restaurants = $restaurantsQuery->limit(4)->get();

        return Inertia::render('Welcome', [
            'cities' => $cities,
            'activeCitySlug' => $activeCitySlug,
            'activeCity' => $activeCity,
            'restaurants' => $restaurants,
            'favoriteIds' => auth()->check() ? auth()->user()->favoriteRestaurants()->pluck('restaurants.id')->toArray() : [],
        ]);
    }

    /**
     * Select the city and store it in the session.
     */
    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'city_slug' => ['required', 'string', 'exists:cities,slug'],
        ]);

        session(['active_city_slug' => $validated['city_slug']]);

        // Redirect back or to restaurants page if it exists later
        return redirect()->back();
    }

    /**
     * Automatically detect the city based on simulated location.
     */
    public function detect(Request $request): JsonResponse
    {
        // For simulation, we detect Seattle as the default nearest city.
        $city = City::where('is_active', true)
            ->where('slug', 'seattle')
            ->first() ?? City::where('is_active', true)->first();

        if (! $city) {
            return response()->json([
                'success' => false,
                'message' => 'No active cities available.',
            ], 404);
        }

        session(['active_city_slug' => $city->slug]);

        return response()->json([
            'success' => true,
            'city' => $city,
        ]);
    }
}
