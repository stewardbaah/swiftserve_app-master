<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Create a new checkout controller instance.
     */
    public function __construct(protected PaystackService $paystackService) {}

    /**
     * Show the checkout page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $latitude = $request->input('latitude') !== null ? (float) $request->input('latitude') : session('customer_lat');
        $longitude = $request->input('longitude') !== null ? (float) $request->input('longitude') : session('customer_lng');

        if ($request->has('latitude') && $request->has('longitude')) {
            session(['customer_lat' => $latitude, 'customer_lng' => $longitude]);
        }

        $cartDetails = CartController::getCartDetails($latitude, $longitude);

        if (empty($cartDetails['items'])) {
            return redirect()->route('restaurants.directory');
        }

        return Inertia::render('Checkout/Show', [
            'cart' => $cartDetails,
            'customerLat' => $latitude,
            'customerLng' => $longitude,
            'savedAddresses' => auth()->user()->savedAddresses()->latest()->get(),
        ]);
    }

    /**
     * Apply a promo code.
     */
    public function applyPromoCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'promo_code' => ['required', 'string'],
        ]);

        $code = strtoupper($validated['promo_code']);
        $restaurantId = session('cart_restaurant_id');

        if (! $restaurantId) {
            return redirect()->back()->withErrors([
                'promo_code' => 'No active restaurant cart.',
            ]);
        }

        $discount = Discount::where('restaurant_id', $restaurantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($discount && ! $discount->isExpired()) {
            session(['promo_code' => $code]);

            return redirect()->back();
        }

        return redirect()->back()->withErrors([
            'promo_code' => 'Invalid or expired promo code.',
        ]);
    }

    /**
     * Remove applied promo code.
     */
    public function removePromoCode(): RedirectResponse
    {
        session()->forget('promo_code');

        return redirect()->back();
    }

    /**
     * Place the order.
     */
    public function store(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'type' => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['required_if:type,delivery', 'nullable', 'string'],
            'special_instructions' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $latitude = ($validated['latitude'] ?? null) !== null ? (float) $validated['latitude'] : session('customer_lat');
        $longitude = ($validated['longitude'] ?? null) !== null ? (float) $validated['longitude'] : session('customer_lng');

        $cartDetails = CartController::getCartDetails($latitude, $longitude);

        if (empty($cartDetails['items'])) {
            return redirect()->route('restaurants.directory')->withErrors([
                'cart' => 'Your cart is empty.',
            ]);
        }

        // Block delivery orders when the customer is outside the restaurant's delivery radius
        if ($validated['type'] === 'delivery' && $cartDetails['outside_delivery_area'] === true) {
            return redirect()->back()->withErrors([
                'delivery_address' => 'This restaurant does not deliver to your selected location. Please choose pickup or select a closer address.',
            ]);
        }

        // Generate unique order number
        $orderNumber = 'SS-'.strtoupper(Str::random(8));
        while (Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber = 'SS-'.strtoupper(Str::random(8));
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'restaurant_id' => $cartDetails['restaurant']->id,
            'order_number' => $orderNumber,
            'status' => 'received',
            'type' => $validated['type'],
            'delivery_address' => $validated['type'] === 'delivery' ? $validated['delivery_address'] : null,
            'subtotal' => $cartDetails['subtotal'],
            'delivery_fee' => $cartDetails['delivery_fee'],
            'service_fee' => $cartDetails['service_fee'],
            'tax' => $cartDetails['tax'],
            'discount' => $cartDetails['discount'],
            'total' => $cartDetails['total'],
            'special_instructions' => $validated['special_instructions'],
            'payment_status' => 'pending',
            'payment_method' => 'paystack',
            'promo_code' => $cartDetails['promo_code'],
            'estimated_ready_time' => now()->addMinutes($cartDetails['restaurant']->estimated_delivery_time),
        ]);

        // Create order items
        foreach ($cartDetails['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'customization' => $item['customization'],
                'notes' => $item['notes'],
            ]);
        }

        // Initialize transaction on Paystack
        $callbackUrl = route('checkout.callback');
        $authUrl = $this->paystackService->initializeTransaction(
            auth()->user()->email,
            $order->total,
            $order->order_number,
            $callbackUrl,
            ['order_id' => $order->id]
        );

        if ($authUrl) {
            return Inertia::location($authUrl);
        }

        // Clean up order if initialization failed
        $order->delete();

        return redirect()->back()->withErrors([
            'error' => 'Unable to initialize payment with Paystack. Please try again.',
        ]);
    }

    /**
     * Handle the Paystack payment redirect callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference');

        if (empty($reference)) {
            return redirect()->route('checkout.show')->withErrors([
                'error' => 'Payment reference was not provided.',
            ]);
        }

        $transaction = $this->paystackService->verifyTransaction($reference);

        if ($transaction && ($transaction['status'] ?? '') === 'success') {
            $order = Order::where('order_number', $reference)->first();

            if ($order) {
                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                    ]);
                }

                // Clear cart session
                session()->forget(['cart', 'cart_restaurant_id', 'promo_code']);

                return redirect()->route('orders.track', $order->id);
            }
        }

        return redirect()->route('checkout.show')->withErrors([
            'error' => 'Payment verification failed. Please try again.',
        ]);
    }

    /**
     * Handle the Paystack webhook.
     */
    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $signature = $request->header('X-Paystack-Signature');
        $secretKey = config('services.paystack.secret_key');

        if (empty($signature) || empty($secretKey)) {
            return response('Invalid configuration or missing signature', 400);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $payload, $secretKey);

        if (! hash_equals($expectedSignature, $signature)) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? '') === 'charge.success') {
            $reference = $event['data']['reference'] ?? null;

            if ($reference) {
                $order = Order::where('order_number', $reference)->first();

                if ($order && $order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                    ]);
                }
            }
        }

        return response('Webhook processed', 200);
    }
}
