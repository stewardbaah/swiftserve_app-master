<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Button } from '@/components/ui/button';
import { HugeiconsIcon } from '@hugeicons/vue';
import { HeartIcon } from '@hugeicons/core-free-icons';

interface MenuItem {
    id: number;
    name: string;
    description: string | null;
    price: number;
    image_path: string | null;
    image_url: string | null;
    is_available: boolean;
}

interface MenuCategory {
    id: number;
    name: string;
    description: string | null;
    menu_items: MenuItem[];
}

interface Restaurant {
    id: number;
    name: string;
    cuisine_type: string;
    description: string | null;
    address: string;
    phone: string | null;
    delivery_fee: number;
    estimated_delivery_time: number;
    estimated_pickup_time: number;
    rating: number;
    rating_count: number;
    image_path: string | null;
    image_url: string | null;
    offers_delivery: boolean;
    offers_pickup: boolean;
    menu_categories: MenuCategory[];
}

const props = defineProps<{
    restaurant: Restaurant;
    cart: {
        items: any[];
        subtotal: number;
        delivery_fee: number;
        service_fee: number;
        tax: number;
        tax_rate: number;
        discount: number;
        total: number;
        promo_code: string | null;
    };
    isFavorited: boolean;
}>();

const toggleFavorite = () => {
    router.post(
        `/restaurants/${props.restaurant.id}/favorite`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const promoForm = useForm({
    promo_code: '',
});

const applyPromo = () => {
    promoForm.post('/checkout/promo', {
        preserveScroll: true,
        onSuccess: () => {
            promoForm.reset();
        },
    });
};

const removePromo = () => {
    useForm({}).post('/checkout/promo/remove', {
        preserveScroll: true,
    });
};

// Selected Item for Customization Modal
const expandedItemId = ref<number | null>(null);
const quantity = ref(1);
const notes = ref('');
const isCartCollapsed = ref(false);

const scrollToCart = () => {
    const el = document.getElementById('cart-section');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
        isCartCollapsed.value = false;
    }
};

const toggleExpand = (item: MenuItem) => {
    if (expandedItemId.value === item.id) {
        expandedItemId.value = null;
    } else {
        expandedItemId.value = item.id;
        quantity.value = 1;
        notes.value = '';
    }
};

// Form submission for adding to cart
const addToCart = (item: MenuItem) => {
    router.post(
        '/cart/add',
        {
            menu_item_id: item.id,
            quantity: quantity.value,
            notes: notes.value || undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                expandedItemId.value = null;
            },
        },
    );
};

const updateQuantity = (key: string, qty: number) => {
    if (qty < 1) {
        removeFromCart(key);
        return;
    }
    router.post(
        '/cart/update',
        {
            key: key,
            quantity: qty,
        },
        { preserveScroll: true },
    );
};

const removeFromCart = (key: string) => {
    router.post('/cart/remove', { key }, { preserveScroll: true });
};

const formatPrice = (priceInCents: number) => {
    return (priceInCents / 100).toLocaleString('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });
};
</script>

<template>
    <CustomerLayout>
        <Head :title="restaurant.name" />

        <!-- Cover Image -->
        <div
            v-if="restaurant.image_path"
            class="relative h-64 w-full overflow-hidden bg-muted sm:h-80 lg:h-96"
        >
            <img
                :src="restaurant.image_url"
                :alt="restaurant.name"
                class="h-full w-full object-cover"
            />
        </div>

        <!-- Header Hero Area (No gradients, no shadows, background separator) -->
        <section class="border-b border-border bg-card py-12">
            <div
                class="mx-auto flex max-w-7xl flex-col justify-between gap-6 px-6 md:flex-row md:items-center md:px-8"
            >
                <div>
                    <h1
                        class="flex items-center gap-3 text-3xl font-extrabold tracking-tight"
                    >
                        <span>{{ restaurant.name }}</span>
                        <button
                            v-if="$page.props.auth.user"
                            @click="toggleFavorite"
                            class="rounded-full p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-red-500"
                            :class="{ 'text-red-500': isFavorited }"
                            title="Toggle Favorite"
                        >
                            <HugeiconsIcon
                                :icon="HeartIcon"
                                class="h-6 w-6"
                                :class="{
                                    'fill-red-500 text-red-500': isFavorited,
                                }"
                            />
                        </button>
                    </h1>
                    <p class="mt-2 text-sm font-medium text-muted-foreground">
                        {{ restaurant.cuisine_type }} &middot;
                        {{ restaurant.address }}
                    </p>
                    <p
                        v-if="restaurant.description"
                        class="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ restaurant.description }}
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center gap-6 text-sm font-semibold text-secondary-foreground"
                >
                    <div
                        class="flex items-center gap-1 rounded-md bg-muted px-3 py-1.5"
                    >
                        <svg
                            class="h-4 w-4 fill-amber-500 text-amber-500"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                            />
                        </svg>
                        <span
                            >{{
                                restaurant.rating > 0
                                    ? restaurant.rating.toFixed(1)
                                    : 'New'
                            }}
                            ({{ restaurant.rating_count }} ratings)</span
                        >
                    </div>
                    <div>
                        Delivery:
                        <strong>{{
                            formatPrice(restaurant.delivery_fee)
                        }}</strong>
                    </div>
                    <div>
                        Time:
                        <strong
                            >{{
                                restaurant.estimated_delivery_time
                            }}
                            mins</strong
                        >
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Body: Menu & Cart Sidebar -->
        <div class="mx-auto max-w-7xl px-6 py-12 md:px-8">
            <div class="grid gap-12 lg:grid-cols-3">
                <!-- Menu List (Cols 1 & 2) -->
                <div class="space-y-12 lg:col-span-2">
                    <div
                        v-for="category in restaurant.menu_categories"
                        :key="category.id"
                        class="space-y-6"
                    >
                        <div class="border-b border-border pb-3">
                            <h2 class="text-xl font-bold tracking-tight">
                                {{ category.name }}
                            </h2>
                            <p
                                v-if="category.description"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ category.description }}
                            </p>
                        </div>

                        <!-- Menu Items list (Whitespace prioritized, no cards, simple row list with bg difference) -->
                        <div class="divide-y divide-border bg-card">
                            <div
                                v-for="item in category.menu_items"
                                :key="item.id"
                                class="flex w-full flex-col gap-4 p-6 text-left transition-colors hover:bg-muted"
                            >
                                <div
                                    class="flex cursor-pointer items-center justify-between gap-6"
                                    @click="toggleExpand(item)"
                                >
                                    <div class="flex-1">
                                        <h3
                                            class="text-base font-bold text-foreground"
                                        >
                                            {{ item.name }}
                                        </h3>
                                        <p
                                            v-if="item.description"
                                            class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                        >
                                            {{ item.description }}
                                        </p>
                                        <span
                                            class="mt-3 inline-block text-sm font-semibold text-foreground"
                                            >{{ formatPrice(item.price) }}</span
                                        >
                                    </div>

                                    <div
                                        v-if="item.image_path"
                                        class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg bg-muted"
                                    >
                                        <img
                                            :src="item.image_url"
                                            :alt="item.name"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                </div>

                                <!-- Expanded Inline Controls -->
                                <div
                                    v-if="expandedItemId === item.id"
                                    class="mt-2 cursor-default space-y-4 border-t border-border pt-4"
                                >
                                    <!-- special instructions -->
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                            >Special Instructions</label
                                        >
                                        <textarea
                                            v-model="notes"
                                            placeholder="Add notes (e.g. allergies, extra sauce, etc.)"
                                            class="w-full rounded-lg border border-border bg-card p-3 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                                            rows="2"
                                        ></textarea>
                                    </div>

                                    <div
                                        class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
                                    >
                                        <div class="flex items-center gap-3">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-10 w-10"
                                                @click="
                                                    quantity > 1
                                                        ? quantity--
                                                        : null
                                                "
                                                >-</Button
                                            >
                                            <span
                                                class="w-8 text-center text-base font-bold"
                                                >{{ quantity }}</span
                                            >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-10 w-10"
                                                @click="quantity++"
                                                >+</Button
                                            >
                                        </div>

                                        <Button
                                            class="flex-1 bg-primary px-6 py-2.5 font-bold text-primary-foreground hover:opacity-90 sm:flex-none"
                                            @click="addToCart(item)"
                                        >
                                            Add {{ quantity }} to Order &middot;
                                            {{
                                                formatPrice(
                                                    item.price * quantity,
                                                )
                                            }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Cart Sidebar (Col 3) -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6 rounded-xl bg-card p-6">
                        <div
                            class="flex items-center justify-between border-b border-border pb-3"
                            id="cart-section"
                        >
                            <h2 class="text-lg font-bold">Your Order</h2>
                            <button
                                v-if="cart.items.length > 0"
                                @click="isCartCollapsed = !isCartCollapsed"
                                class="p-1 text-muted-foreground hover:text-foreground/80"
                            >
                                <svg
                                    v-if="!isCartCollapsed"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m18 15-6-6-6 6" />
                                </svg>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>

                        <!-- Cart Items -->
                        <div v-if="cart.items.length > 0" class="space-y-4">
                            <!-- Items List -->
                            <div v-show="!isCartCollapsed" class="space-y-4">
                                <div
                                    v-for="item in cart.items"
                                    :key="item.key"
                                    class="flex items-start justify-between gap-4 text-sm font-medium"
                                >
                                    <div class="flex-1">
                                        <div>{{ item.name }}</div>
                                        <div
                                            v-if="item.notes"
                                            class="mt-0.5 text-xs text-muted-foreground"
                                        >
                                            "{{ item.notes }}"
                                        </div>
                                        <div
                                            class="mt-2 flex items-center gap-3 text-xs"
                                        >
                                            <button
                                                @click="
                                                    updateQuantity(
                                                        item.key,
                                                        item.quantity - 1,
                                                    )
                                                "
                                                class="bg-muted p-1 hover:bg-muted"
                                            >
                                                -
                                            </button>
                                            <span>{{ item.quantity }}</span>
                                            <button
                                                @click="
                                                    updateQuantity(
                                                        item.key,
                                                        item.quantity + 1,
                                                    )
                                                "
                                                class="bg-muted p-1 hover:bg-muted"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div>{{ formatPrice(item.total) }}</div>
                                        <button
                                            @click="removeFromCart(item.key)"
                                            class="mt-2 text-xs text-red-500 hover:underline"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Promo Code / Discount Checking Section -->
                            <div class="border-t border-border pt-4 pb-2">
                                <label class="block text-[10px] font-bold tracking-wider text-muted-foreground uppercase mb-2">Promo Code</label>
                                
                                <div v-if="cart.promo_code" class="flex items-center justify-between bg-green-50 p-2.5 rounded-lg text-xs font-bold text-green-700 dark:bg-green-950/20">
                                    <span>Applied: {{ cart.promo_code }}</span>
                                    <button type="button" @click="removePromo" class="text-[10px] tracking-widest uppercase underline text-green-700 hover:text-green-800 dark:text-green-400">
                                        Remove
                                    </button>
                                </div>
                                
                                <div v-else class="flex gap-2">
                                    <input
                                        v-model="promoForm.promo_code"
                                        type="text"
                                        placeholder="Enter promo code"
                                        class="flex-1 rounded-lg border-0 bg-muted p-2 text-xs text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="applyPromo"
                                        :disabled="promoForm.processing"
                                        class="rounded-lg bg-secondary px-3 py-2 text-xs font-bold text-secondary-foreground transition-all hover:bg-secondary/80 disabled:opacity-50"
                                    >
                                        Apply
                                    </button>
                                </div>
                                <div v-if="promoForm.errors.promo_code" class="mt-1 text-[10px] text-red-500 font-semibold">
                                    {{ promoForm.errors.promo_code }}
                                </div>
                            </div>

                            <!-- Totals summary -->
                            <div
                                class="space-y-2 border-t border-border pt-4 text-xs font-semibold text-muted-foreground"
                            >
                                <div class="flex justify-between">
                                    <span>Subtotal</span>
                                    <span>{{
                                        formatPrice(cart.subtotal)
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Delivery Fee</span>
                                    <span>{{
                                        formatPrice(cart.delivery_fee)
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Service Fee</span>
                                    <span>{{
                                        formatPrice(cart.service_fee)
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tax ({{ cart.tax_rate }}%)</span>
                                    <span>{{ formatPrice(cart.tax) }}</span>
                                </div>
                                <div
                                    v-if="cart.discount > 0"
                                    class="flex justify-between text-green-600"
                                >
                                    <span>Discount<span v-if="cart.promo_code"> ({{ cart.promo_code }})</span></span>
                                    <span
                                        >-{{ formatPrice(cart.discount) }}</span
                                    >
                                </div>
                                <div
                                    class="flex justify-between border-t border-border pt-2 text-sm font-bold text-foreground"
                                >
                                    <span>Total</span>
                                    <span>{{ formatPrice(cart.total) }}</span>
                                </div>
                            </div>

                            <!-- Checkout button -->
                            <Link
                                href="/checkout"
                                class="block w-full rounded-lg bg-primary py-3 text-center text-xs font-bold text-primary-foreground transition-all hover:opacity-90"
                            >
                                Proceed to Checkout
                            </Link>
                        </div>

                        <!-- Empty state -->
                        <div
                            v-else
                            class="py-12 text-center text-sm font-medium text-muted-foreground"
                        >
                            <svg
                                class="mx-auto mb-3 h-8 w-8 text-muted-foreground"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                                />
                            </svg>
                            <p>
                                Select items from the menu to start building
                                your order.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Cart Button -->
        <button
            v-if="cart.items.length > 0"
            @click="scrollToCart"
            class="fixed right-6 bottom-6 z-50 flex items-center gap-3 rounded-full bg-primary px-5 py-3 font-bold text-primary-foreground shadow-2xl transition-opacity hover:opacity-90 lg:hidden"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="8" cy="21" r="1" />
                <circle cx="19" cy="21" r="1" />
                <path
                    d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"
                />
            </svg>
            <span>View Cart ({{ cart.items.length }})</span>
        </button>

        <!-- Removed Item Customization Dialog -->
    </CustomerLayout>
</template>
