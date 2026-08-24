<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Submit a rating and review for a completed order.
     */
    public function store(Order $order, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($order->status !== 'completed') {
            return redirect()->back()->withErrors([
                'order' => 'You can only review completed orders.',
            ]);
        }

        if ($order->review()->exists()) {
            return redirect()->back()->withErrors([
                'order' => 'You have already reviewed this order.',
            ]);
        }

        DB::transaction(function () use ($order, $validated) {
            Review::create([
                'user_id' => auth()->id() ?? $order->user_id, // supports guest if we save guest reviews
                'restaurant_id' => $order->restaurant_id,
                'order_id' => $order->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // Update restaurant average rating and count
            $restaurant = $order->restaurant;
            $newCount = $restaurant->rating_count + 1;
            $newRating = (($restaurant->rating * $restaurant->rating_count) + $validated['rating']) / $newCount;

            $restaurant->update([
                'rating' => round($newRating, 2),
                'rating_count' => $newCount,
            ]);
        });

        return redirect()->back();
    }
}
