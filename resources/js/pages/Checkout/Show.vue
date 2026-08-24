<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';

interface CartItem {
    key: string;
    menu_item_id: number;
    name: string;
    price: number;
    quantity: number;
    notes: string | null;
    customization: any;
    total: number;
}

interface Restaurant {
    id: number;
    name: string;
    delivery_fee: number;
    estimated_delivery_time: number;
    latitude: number | null;
    longitude: number | null;
}

interface SavedAddress {
    id: number;
    user_id: number;
    label: string;
    address_line1: string;
    address_line2: string | null;
    city: string;
    state: string;
    postal_code: string;
    latitude?: number | null;
    longitude?: number | null;
}

const props = defineProps<{
    cart: {
        items: CartItem[];
        subtotal: number;
        delivery_fee: number;
        service_fee: number;
        tax: number;
        tax_rate: number;
        discount: number;
        total: number;
        restaurant: Restaurant;
        promo_code: string | null;
        distance: number | null;
        outside_delivery_area: boolean | null;
    };
    customerLat: number | null;
    customerLng: number | null;
    savedAddresses: SavedAddress[];
}>();

const selectedSavedAddressId = ref<number | 'new' | ''>('');

const form = useForm({
    type: 'delivery', // delivery or pickup
    delivery_address: '',
    special_instructions: '',
    latitude: props.customerLat || '',
    longitude: props.customerLng || '',
});

const mapContainer = ref<HTMLDivElement | null>(null);
let map: any = null;
let deliveryMarker: any = null;
let restaurantMarker: any = null;
let routeLine: any = null;
const isLocating = ref(false);
const isGeocoding = ref(false);

const updateDeliveryFee = () => {
    router.reload({
        data: {
            latitude: form.latitude,
            longitude: form.longitude,
        },
        only: ['cart'],
    });
};

const promoForm = useForm({
    promo_code: '',
});

const submitOrder = () => {
    if (!canPlaceOrder.value) return;
    form.post('/checkout');
};

const isOutsideRange = computed(() =>
    form.type === 'delivery' && props.cart.outside_delivery_area === true,
);

const canPlaceOrder = computed(() =>
    !form.processing && !isOutsideRange.value,
);

const applyPromo = () => {
    promoForm.post('/checkout/promo', {
        onSuccess: () => {
            promoForm.reset();
        },
    });
};

const removePromo = () => {
    useForm({}).post('/checkout/promo/remove');
};

const formatPrice = (priceInCents: number) => {
    return (priceInCents / 100).toLocaleString('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });
};

const destroyMap = () => {
    if (map) {
        map.remove();
        map = null;
        deliveryMarker = null;
        restaurantMarker = null;
        routeLine = null;
    }
};

onUnmounted(() => {
    destroyMap();
});

const initMap = () => {
    nextTick(() => {
        setTimeout(() => {
            if (!mapContainer.value) return;

            if (typeof (window as any).L === 'undefined') {
                setTimeout(initMap, 100);
                return;
            }

            const L = (window as any).L;

            const defaultLat = 5.6037; // Accra
            const defaultLng = -0.1870;

            const lat = form.latitude ? parseFloat(String(form.latitude)) : defaultLat;
            const lng = form.longitude ? parseFloat(String(form.longitude)) : defaultLng;

            if (map) {
                map.remove();
            }

            map = L.map(mapContainer.value).setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            // Add restaurant marker if coordinates are available
            if (props.cart.restaurant && props.cart.restaurant.latitude !== null && props.cart.restaurant.longitude !== null) {
                const restLat = parseFloat(String(props.cart.restaurant.latitude));
                const restLng = parseFloat(String(props.cart.restaurant.longitude));
                
                restaurantMarker = L.marker([restLat, restLng], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map).bindPopup(`<strong>${props.cart.restaurant.name}</strong> (Restaurant Kitchen)`).openPopup();
            }

            // Add delivery marker (draggable)
            deliveryMarker = L.marker([lat, lng], { draggable: true }).addTo(map).bindPopup('<strong>Your Delivery Location</strong>').openPopup();

            const updateCoords = (newLat: number, newLng: number) => {
                form.latitude = parseFloat(String(newLat)).toFixed(8);
                form.longitude = parseFloat(String(newLng)).toFixed(8);
                updateDeliveryFee();
                drawRoute();
            };

            deliveryMarker.on('dragend', (e: any) => {
                const position = e.target.getLatLng();
                updateCoords(position.lat, position.lng);
                map.panTo(position);
                reverseGeocode(position.lat, position.lng);
            });

            map.on('click', (e: any) => {
                const position = e.latlng;
                deliveryMarker.setLatLng(position);
                updateCoords(position.lat, position.lng);
                map.panTo(position);
                reverseGeocode(position.lat, position.lng);
            });

            drawRoute();
            fitMapBounds();
        }, 200);
    });
};

const drawRoute = () => {
    if (!map) return;
    const L = (window as any).L;

    if (routeLine) {
        map.removeLayer(routeLine);
        routeLine = null;
    }

    if (props.cart.restaurant && props.cart.restaurant.latitude !== null && props.cart.restaurant.longitude !== null && form.latitude && form.longitude) {
        const restLat = parseFloat(String(props.cart.restaurant.latitude));
        const restLng = parseFloat(String(props.cart.restaurant.longitude));
        const delLat = parseFloat(String(form.latitude));
        const delLng = parseFloat(String(form.longitude));

        routeLine = L.polyline([[restLat, restLng], [delLat, delLng]], {
            color: '#ef4444',
            weight: 3,
            dashArray: '5, 10',
            opacity: 0.8
        }).addTo(map);
    }
};

const fitMapBounds = () => {
    if (!map) return;
    const L = (window as any).L;

    if (props.cart.restaurant && props.cart.restaurant.latitude !== null && props.cart.restaurant.longitude !== null && form.latitude && form.longitude) {
        const restLat = parseFloat(String(props.cart.restaurant.latitude));
        const restLng = parseFloat(String(props.cart.restaurant.longitude));
        const delLat = parseFloat(String(form.latitude));
        const delLng = parseFloat(String(form.longitude));

        const bounds = L.latLngBounds([[restLat, restLng], [delLat, delLng]]);
        map.fitBounds(bounds, { padding: [40, 40] });
    }
};

const reverseGeocode = async (lat: number, lng: number) => {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18`, {
            headers: {
                'Accept-Language': 'en'
            }
        });
        if (response.ok) {
            const data = await response.json();
            if (data && data.display_name) {
                form.delivery_address = data.display_name;
            }
        }
    } catch (e) {
        console.error('Reverse geocoding error:', e);
    }
};

const locateAddress = async () => {
    if (!form.delivery_address) return;
    
    isGeocoding.value = true;
    try {
        const query = encodeURIComponent(form.delivery_address);
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`, {
            headers: {
                'Accept-Language': 'en'
            }
        });
        if (response.ok) {
            const data = await response.json();
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);

                form.latitude = lat.toFixed(8);
                form.longitude = lng.toFixed(8);

                if (map && deliveryMarker) {
                    const L = (window as any).L;
                    const newPos = new L.LatLng(lat, lng);
                    deliveryMarker.setLatLng(newPos);
                    map.setView(newPos, 15);
                    drawRoute();
                    fitMapBounds();
                }
                updateDeliveryFee();
            } else {
                alert('Could not find that address on the map. Please select it manually.');
            }
        }
    } catch (e) {
        console.error('Forward geocoding error:', e);
    } finally {
        isGeocoding.value = false;
    }
};

const useCurrentLocation = () => {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            isLocating.value = false;
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            form.latitude = parseFloat(String(lat)).toFixed(8);
            form.longitude = parseFloat(String(lng)).toFixed(8);

            if (map && deliveryMarker) {
                const L = (window as any).L;
                const newPos = new L.LatLng(lat, lng);
                deliveryMarker.setLatLng(newPos);
                map.setView(newPos, 15);
                drawRoute();
                fitMapBounds();
            }
            updateDeliveryFee();
            reverseGeocode(lat, lng);
        },
        (error) => {
            isLocating.value = false;
            console.error('Geolocation error:', error);
            alert('Unable to retrieve your location: ' + error.message);
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
};

watch(selectedSavedAddressId, (newId) => {
    if (newId === 'new' || newId === '') {
        form.delivery_address = '';
        form.latitude = '';
        form.longitude = '';
        updateDeliveryFee();
        
        if (form.type === 'delivery') {
            initMap();
        }
    } else {
        const address = props.savedAddresses.find(a => a.id === newId);
        if (address) {
            form.delivery_address = `${address.address_line1}${address.address_line2 ? ', ' + address.address_line2 : ''}, ${address.city}, ${address.state} ${address.postal_code}`;
            form.latitude = address.latitude !== undefined && address.latitude !== null ? String(address.latitude) : '';
            form.longitude = address.longitude !== undefined && address.longitude !== null ? String(address.longitude) : '';
            updateDeliveryFee();

            if (form.latitude && form.longitude && map && deliveryMarker) {
                const L = (window as any).L;
                const newPos = new L.LatLng(parseFloat(form.latitude), parseFloat(form.longitude));
                deliveryMarker.setLatLng(newPos);
                map.setView(newPos, 15);
                drawRoute();
                fitMapBounds();
            } else if (form.type === 'delivery') {
                initMap();
            }
        }
    }
});

watch(() => form.type, (newType) => {
    if (newType === 'delivery') {
        initMap();
    } else {
        destroyMap();
    }
});

onMounted(() => {
    if (form.type === 'delivery') {
        if (props.savedAddresses && props.savedAddresses.length > 0) {
            selectedSavedAddressId.value = props.savedAddresses[0].id;
        } else {
            selectedSavedAddressId.value = 'new';
        }
        initMap();
    }
});
</script>

<template>
    <CustomerLayout>
        <Head title="Checkout" />

        <div class="mx-auto max-w-7xl px-6 py-12 md:px-8">
            <h1 class="mb-10 text-3xl font-extrabold tracking-tight">
                Checkout
            </h1>

            <div class="grid gap-12 lg:grid-cols-3">
                <!-- Left Details & Forms (Cols 1 & 2) -->
                <div class="space-y-8 lg:col-span-2">
                    <!-- Delivery vs. Pickup Options -->
                    <div class="space-y-4 rounded-xl bg-card p-6">
                        <h2 class="text-lg font-bold">Fulfillment Method</h2>

                        <div class="flex gap-4">
                            <button
                                @click="form.type = 'delivery'"
                                type="button"
                                class="flex-1 border-0 py-4 text-center text-sm font-semibold transition-all focus:outline-none"
                                :class="
                                    form.type === 'delivery'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-zinc-600'
                                "
                            >
                                Delivery
                            </button>

                            <button
                                @click="form.type = 'pickup'"
                                type="button"
                                class="flex-1 border-0 py-4 text-center text-sm font-semibold transition-all focus:outline-none"
                                :class="
                                    form.type === 'pickup'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-zinc-600'
                                "
                            >
                                Pickup
                            </button>
                        </div>

                        <!-- Delivery Address Fields -->
                        <div
                            v-if="form.type === 'delivery'"
                            class="mt-4 space-y-4 animate-in fade-in duration-300"
                        >
                            <!-- Saved Addresses List -->
                            <div v-if="savedAddresses.length > 0" class="space-y-2">
                                <label class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Choose Delivery Address
                                </label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div
                                        v-for="address in savedAddresses"
                                        :key="address.id"
                                        @click="selectedSavedAddressId = address.id"
                                        class="cursor-pointer rounded-lg p-4 transition-all"
                                        :class="
                                            selectedSavedAddressId === address.id
                                                ? 'bg-primary/10 border-2 border-primary'
                                                : 'bg-muted/40 border border-transparent hover:bg-muted/60'
                                        "
                                    >
                                        <div class="font-bold text-sm text-foreground">{{ address.label }}</div>
                                        <div class="text-xs text-muted-foreground mt-1 line-clamp-2">
                                            {{ address.address_line1 }}<span v-if="address.address_line2">, {{ address.address_line2 }}</span>, {{ address.city }}
                                        </div>
                                    </div>
                                    <div
                                        @click="selectedSavedAddressId = 'new'"
                                        class="cursor-pointer rounded-lg p-4 transition-all flex flex-col justify-center items-center text-center"
                                        :class="
                                            selectedSavedAddressId === 'new'
                                                ? 'bg-primary/10 border-2 border-primary'
                                                : 'bg-muted/40 border border-transparent hover:bg-muted/60'
                                        "
                                    >
                                        <div class="font-bold text-sm text-primary">New Address</div>
                                        <div class="text-xs text-muted-foreground mt-1">
                                            Enter a one-time address
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Input fields (visible when selectedSavedAddressId is 'new' or there are no saved addresses) -->
                            <div v-if="selectedSavedAddressId === 'new' || savedAddresses.length === 0" class="space-y-3 animate-in fade-in duration-300">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                            Delivery Address
                                        </label>
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                class="text-xs font-bold text-primary hover:underline bg-transparent border-0 cursor-pointer"
                                                @click="useCurrentLocation"
                                                :disabled="isLocating"
                                            >
                                                {{ isLocating ? 'Locating...' : 'Use Current Location' }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <input
                                            v-model="form.delivery_address"
                                            type="text"
                                            placeholder="Enter your full street address"
                                            class="flex-1 border-0 bg-muted p-3 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="bg-muted px-4 text-xs font-bold text-foreground transition-colors hover:bg-secondary border-0 cursor-pointer"
                                            @click="locateAddress"
                                            :disabled="isGeocoding"
                                        >
                                            {{ isGeocoding ? 'Locating...' : 'Pin on Map' }}
                                        </button>
                                    </div>
                                    <div
                                        v-if="form.errors.delivery_address"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ form.errors.delivery_address }}
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Map Selection -->
                            <div class="space-y-2">
                                <label class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Select location on map
                                </label>
                                <div ref="mapContainer" class="h-[220px] w-full rounded-lg border border-border bg-muted/40 relative animate-in fade-in duration-300" style="z-index: 1;"></div>
                                
                                <div class="flex justify-between items-center text-[10px] text-muted-foreground px-1">
                                    <div>Coordinates: {{ form.latitude ? parseFloat(String(form.latitude)).toFixed(5) : 'Not set' }}, {{ form.longitude ? parseFloat(String(form.longitude)).toFixed(5) : 'Not set' }}</div>
                                    <div v-if="cart.distance !== null">Distance: {{ cart.distance }} km</div>
                                </div>

                                <!-- Out-of-range alert -->
                                <div
                                    v-if="isOutsideRange"
                                    class="rounded-lg bg-destructive/10 px-4 py-3 text-xs font-semibold text-destructive"
                                >
                                    This restaurant doesn't deliver to your selected location
                                    <span v-if="cart.distance !== null">({{ cart.distance }} km away)</span>.
                                    Please move the pin closer or switch to
                                    <button
                                        type="button"
                                        class="underline font-bold bg-transparent border-0 cursor-pointer text-destructive"
                                        @click="form.type = 'pickup'"
                                    >Pickup</button>.
                                </div>

                                <!-- Within-range confirmation -->
                                <div
                                    v-else-if="cart.outside_delivery_area === false"
                                    class="rounded-lg bg-green-500/10 px-4 py-3 text-xs font-semibold text-green-700 dark:text-green-400"
                                >
                                    Within delivery range.
                                </div>
                            </div>
                        </div>

                        <!-- Special Instructions -->
                        <div class="mt-4 space-y-2">
                            <label
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Driver / Kitchen Notes</label
                            >
                            <textarea
                                v-model="form.special_instructions"
                                placeholder="Add notes (e.g. drop off at front desk, gate code, etc.)"
                                class="w-full border-0 bg-muted p-3 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                                rows="2"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="space-y-4 rounded-xl bg-card p-6">
                        <h2 class="text-lg font-bold">Payment Method</h2>

                        <div class="space-y-4">
                            <div class="rounded-lg bg-muted/40 p-4">
                                <p class="text-sm font-medium text-foreground">
                                    Secure Payment via Paystack
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    You will be redirected to Paystack's secure checkout page to complete your transaction. We support Card, Mobile Money, and Bank Transfer.
                                </p>
                            </div>

                            <div
                                v-if="form.errors.error"
                                class="rounded-lg bg-destructive/15 p-4 text-xs font-semibold text-destructive"
                            >
                                {{ form.errors.error }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side Summary (Col 3) -->
                <div class="space-y-6 lg:col-span-1">
                    <div class="space-y-6 rounded-xl bg-card p-6">
                        <h2
                            class="border-b border-border pb-3 text-lg font-bold"
                        >
                            Order Summary
                        </h2>
                        <div
                            class="mb-4 text-xs font-semibold text-muted-foreground"
                        >
                            From <strong>{{ cart.restaurant.name }}</strong>
                        </div>

                        <!-- Items list -->
                        <div class="max-h-[300px] space-y-4 overflow-y-auto">
                            <div
                                v-for="item in cart.items"
                                :key="item.key"
                                class="flex justify-between text-sm font-medium"
                            >
                                <span
                                    >{{ item.quantity }}x {{ item.name }}</span
                                >
                                <span>{{ formatPrice(item.total) }}</span>
                            </div>
                        </div>

                        <!-- Promo Code input -->
                        <div
                            class="space-y-2 border-t border-b border-border py-6"
                        >
                            <label
                                class="block text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                >Promo Code</label
                            >

                            <div
                                v-if="cart.promo_code"
                                class="mt-2 flex items-center justify-between bg-green-50 p-3 text-xs font-bold text-green-700 dark:bg-green-950/20"
                            >
                                <span>Applied: {{ cart.promo_code }}</span>
                                <button
                                    type="button"
                                    @click="removePromo"
                                    class="text-[10px] tracking-widest uppercase underline"
                                >
                                    Remove
                                </button>
                            </div>

                            <div v-else class="flex gap-2">
                                <input
                                    v-model="promoForm.promo_code"
                                    type="text"
                                    placeholder="WELCOME10"
                                    class="flex-1 border-0 bg-muted p-2.5 text-xs text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                                />
                                <button
                                    type="button"
                                    @click="applyPromo"
                                    class="bg-muted px-4 text-xs font-bold text-foreground transition-colors hover:bg-secondary"
                                >
                                    Apply
                                </button>
                            </div>
                            <div
                                v-if="promoForm.errors.promo_code"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ promoForm.errors.promo_code }}
                            </div>
                        </div>

                        <!-- Totals summary -->
                        <div
                            class="space-y-2 text-xs font-semibold text-muted-foreground"
                        >
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>{{ formatPrice(cart.subtotal) }}</span>
                            </div>
                            <div
                                v-if="form.type === 'delivery'"
                                class="flex items-baseline justify-between"
                            >
                                <div class="flex flex-col">
                                    <span>Delivery Fee</span>
                                    <span
                                        v-if="cart.distance !== null"
                                        class="text-[10px] font-medium text-muted-foreground"
                                    >
                                        Distance: {{ cart.distance }} km
                                    </span>
                                </div>
                                <span>{{
                                    formatPrice(cart.delivery_fee)
                                }}</span>
                            </div>
                            <div
                                v-else
                                class="flex justify-between text-green-600"
                            >
                                <span>Delivery Fee</span>
                                <span>Free (Pickup)</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Service Fee</span>
                                <span>{{ formatPrice(cart.service_fee) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tax ({{ cart.tax_rate }}%)</span>
                                <span>{{ formatPrice(cart.tax) }}</span>
                            </div>
                            <div
                                v-if="cart.discount > 0"
                                class="flex justify-between text-green-600"
                            >
                                <span>Discount</span>
                                <span>-{{ formatPrice(cart.discount) }}</span>
                            </div>
                            <div
                                class="flex justify-between border-t border-border pt-2 text-sm font-bold text-foreground"
                            >
                                <span>Total</span>
                                <span>{{
                                    formatPrice(
                                        form.type === 'delivery'
                                            ? cart.total
                                            : Math.max(
                                                  0,
                                                  cart.total -
                                                      cart.delivery_fee,
                                              ),
                                    )
                                }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitOrder"
                            :disabled="!canPlaceOrder"
                            class="w-full rounded-lg bg-primary py-3.5 text-center text-xs font-bold text-primary-foreground transition-all hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{
                                form.processing
                                    ? 'Placing Order...'
                                    : isOutsideRange
                                      ? 'Outside Delivery Range'
                                      : 'Place Order'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
