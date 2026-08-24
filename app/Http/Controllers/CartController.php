<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Add an item to the cart.
     */
    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'exists:menu_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'customization' => ['nullable', 'array'],
        ]);

        $menuItem = MenuItem::with('restaurant')->findOrFail($validated['menu_item_id']);
        $restaurant = $menuItem->restaurant;

        $cart = session('cart', []);
        $restaurantId = session('cart_restaurant_id');

        // If cart has items from a different restaurant, clear it first
        if ($restaurantId && $restaurantId !== $restaurant->id) {
            $cart = [];
        }

        session(['cart_restaurant_id' => $restaurant->id]);

        $customization = $validated['customization'] ?? [];
        $key = $menuItem->id.'_'.md5(serialize($customization));

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $validated['quantity'];
        } else {
            $cart[$key] = [
                'menu_item_id' => $menuItem->id,
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
                'customization' => $customization,
            ];
        }

        session(['cart' => $cart]);

        return redirect()->back();
    }

    /**
     * Update the quantity of an item in the cart.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = session('cart', []);

        if (isset($cart[$validated['key']])) {
            $cart[$validated['key']]['quantity'] = $validated['quantity'];
            session(['cart' => $cart]);
        }

        return redirect()->back();
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
        ]);

        $cart = session('cart', []);

        if (isset($cart[$validated['key']])) {
            unset($cart[$validated['key']]);
            session(['cart' => $cart]);

            if (empty($cart)) {
                session()->forget(['cart', 'cart_restaurant_id', 'promo_code']);
            }
        }

        return redirect()->back();
    }

    /**
     * Clear the cart.
     */
    public function clear(): RedirectResponse
    {
        session()->forget(['cart', 'cart_restaurant_id', 'promo_code']);

        return redirect()->back();
    }

    /**
     * Calculate Haversine distance between two points in km.
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Format and return cart details with totals.
     */
    public static function getCartDetails(?float $latitude = null, ?float $longitude = null): array
    {
        $cart = session('cart', []);
        $restaurantId = session('cart_restaurant_id');

        if (empty($cart) || ! $restaurantId) {
            return [
                'items' => [],
                'subtotal' => 0,
                'delivery_fee' => 0,
                'service_fee' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'restaurant' => null,
                'promo_code' => null,
                'distance' => null,
                'outside_delivery_area' => null,
            ];
        }

        $restaurant = Restaurant::find($restaurantId);
        $formattedItems = [];
        $subtotal = 0;

        foreach ($cart as $key => $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            if (! $menuItem) {
                continue;
            }

            $itemTotal = $menuItem->price * $item['quantity'];
            $subtotal += $itemTotal;

            $formattedItems[] = [
                'key' => $key,
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->price,
                'image_path' => $menuItem->image_path,
                'quantity' => $item['quantity'],
                'notes' => $item['notes'],
                'customization' => $item['customization'],
                'total' => $itemTotal,
            ];
        }

        $deliveryFee = $restaurant ? $restaurant->delivery_fee : 0;
        $distance = null;
        $outsideDeliveryArea = null;

        if ($restaurant && $latitude !== null && $longitude !== null && $restaurant->latitude !== null && $restaurant->longitude !== null) {
            $distance = self::calculateDistance(
                (float) $restaurant->latitude,
                (float) $restaurant->longitude,
                $latitude,
                $longitude
            );
            // GHC 2.00 base + GHC 1.50 per kilometer
            $deliveryFee = 200 + (int) round($distance * 150);
            $distance = round($distance, 2);

            $outsideDeliveryArea = ! $restaurant->deliversToCoordinates($latitude, $longitude);
        }

        $serviceFee = 200; // flat $2.00 service fee

        // Calculate tax based on configured taxes for this restaurant
        $tax = 0;
        if ($restaurant) {
            $activeTaxesQuery = $restaurant->taxes()->where('is_active', true);
            if ($activeTaxesQuery->exists()) {
                $taxRate = $activeTaxesQuery->sum('rate');
                $tax = (int) round($subtotal * ($taxRate / 100));
            } else {
                $tax = (int) ($subtotal * 0.08); // default fallback if no taxes configured
            }
        } else {
            $tax = (int) ($subtotal * 0.08); // fallback
        }

        // Calculate discount based on configured discounts/promo codes
        $discount = 0;
        $promoCode = session('promo_code');
        if ($restaurant && $promoCode) {
            $discountRecord = $restaurant->discounts()
                ->where('code', $promoCode)
                ->where('is_active', true)
                ->first();

            if ($discountRecord) {
                $discount = $discountRecord->calculateDiscount($subtotal);
            }
        }

        // Determine active tax rate
        $taxRate = 8.00; // default fallback
        if ($restaurant) {
            $activeTaxesQuery = $restaurant->taxes()->where('is_active', true);
            if ($activeTaxesQuery->exists()) {
                $taxRate = (float) $activeTaxesQuery->sum('rate');
            }
        }

        $total = max(0, $subtotal + $deliveryFee + $serviceFee + $tax - $discount);

        return [
            'items' => $formattedItems,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'discount' => $discount,
            'total' => $total,
            'restaurant' => $restaurant,
            'promo_code' => $promoCode,
            'distance' => $distance,
            'outside_delivery_area' => $outsideDeliveryArea,
        ];
    }
}
