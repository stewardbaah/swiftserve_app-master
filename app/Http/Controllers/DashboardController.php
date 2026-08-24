<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SavedAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // 1. Fetch active orders (status not completed or cancelled)
        $activeOrders = Order::query()
            ->with(['restaurant'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->get();

        // 2. Fetch recent past orders (last 3 for quick overview)
        $pastOrders = Order::query()
            ->with(['restaurant', 'orderItems.menuItem', 'review', 'complaint'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->latest()
            ->take(3)
            ->get();

        // 3. Fetch recent saved addresses (last 3 for overview)
        $savedAddresses = SavedAddress::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        // 4. Calculate lifetime statistics
        $totalOrdersCount = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $totalSpent = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total');

        return Inertia::render('Dashboard', [
            'activeOrders' => $activeOrders,
            'pastOrders' => $pastOrders,
            'savedAddresses' => $savedAddresses,
            'stats' => [
                'totalOrdersCount' => $totalOrdersCount,
                'totalSpent' => (int) $totalSpent,
            ],
        ]);
    }

    /**
     * Display the full list of orders.
     */
    public function orders(Request $request): Response
    {
        $user = $request->user();

        $activeOrders = Order::query()
            ->with(['restaurant'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->get();

        $pastOrders = Order::query()
            ->with(['restaurant', 'orderItems.menuItem', 'review', 'complaint'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Dashboard/Orders', [
            'activeOrders' => $activeOrders,
            'pastOrders' => $pastOrders,
        ]);
    }

    /**
     * Display payment history and statistics.
     */
    public function payments(Request $request): Response
    {
        $user = $request->user();

        // Fetch paginated transactions (all orders represent payment details)
        $transactions = Order::query()
            ->with(['restaurant'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        $totalOrdersCount = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $totalSpent = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total');

        $averageSpent = $totalOrdersCount > 0 ? (int) ($totalSpent / $totalOrdersCount) : 0;

        return Inertia::render('Dashboard/Payments', [
            'transactions' => $transactions,
            'stats' => [
                'totalOrdersCount' => $totalOrdersCount,
                'totalSpent' => (int) $totalSpent,
                'averageSpent' => $averageSpent,
            ],
        ]);
    }

    /**
     * Display all saved addresses.
     */
    public function addresses(Request $request): Response
    {
        $user = $request->user();

        $savedAddresses = SavedAddress::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard/Addresses', [
            'savedAddresses' => $savedAddresses,
        ]);
    }

    /**
     * Display all favorite restaurants.
     */
    public function favorites(Request $request): Response
    {
        $user = $request->user();

        $favorites = $user->favoriteRestaurants()
            ->approved()
            ->where('is_active', true)
            ->get();

        return Inertia::render('Dashboard/Favorites', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Reorder an existing order.
     */
    public function reorder(Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        // Load items if not loaded
        $order->load('orderItems');

        // Clear current cart first
        session()->forget(['cart', 'cart_restaurant_id', 'promo_code']);

        session(['cart_restaurant_id' => $order->restaurant_id]);

        $cart = [];
        foreach ($order->orderItems as $item) {
            $key = $item->menu_item_id.'_'.md5(serialize($item->customization ?? []));
            $cart[$key] = [
                'menu_item_id' => $item->menu_item_id,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'customization' => $item->customization ?? [],
            ];
        }

        session(['cart' => $cart]);

        return redirect()->route('checkout.show');
    }
}
