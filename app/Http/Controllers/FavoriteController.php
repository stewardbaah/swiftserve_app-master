<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Toggle the favorite status of a restaurant for the authenticated user.
     */
    public function toggle(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $user = $request->user();

        $user->favoriteRestaurants()->toggle($restaurant->id);

        return back();
    }
}
