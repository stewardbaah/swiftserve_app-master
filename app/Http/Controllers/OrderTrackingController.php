<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    /**
     * Display the order tracking page.
     */
    public function track(Order $order): Response
    {
        $order->load(['restaurant', 'orderItems.menuItem', 'review', 'complaint']);

        return Inertia::render('Orders/Track', [
            'order' => $order,
        ]);
    }
}
