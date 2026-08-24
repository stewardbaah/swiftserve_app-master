<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    /**
     * Store a new complaint for an order.
     */
    public function store(Order $order, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string', 'max:2000'],
        ]);

        if ($order->user_id !== auth()->id() && ! auth()->guest()) {
            abort(403);
        }

        if ($order->complaint()->exists()) {
            return redirect()->back()->withErrors([
                'complaint' => 'A complaint has already been submitted for this order.',
            ]);
        }

        Complaint::create([
            'user_id' => auth()->id(),
            'restaurant_id' => $order->restaurant_id,
            'order_id' => $order->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'],
            'status' => 'pending',
        ]);

        return redirect()->back();
    }
}
