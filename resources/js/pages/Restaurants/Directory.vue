<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { HeartIcon } from '@hugeicons/core-free-icons';

interface Restaurant {
    id: number;
    name: string;
    slug: string;
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
}

interface City {
    id: number;
    name: string;
    state: string;
    slug: string;
}

const props = defineProps<{
    restaurants: Restaurant[];
    cuisineTypes: string[];
    activeCity: City;
    filters: {
        search?: string;
        cuisine?: string;
        offers_delivery?: string;
        offers_pickup?: string;
        sort?: string;
    };
    favoriteIds: number[];
}>();

const toggleFavorite = (id: number) => {
    router.post(
        `/restaurants/${id}/favorite`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const search = ref(props.filters.search || '');
const cuisine = ref(props.filters.cuisine || '');
const offersDelivery = ref(props.filters.offers_delivery === 'true');
const offersPickup = ref(props.filters.offers_pickup === 'true');
const sort = ref(props.filters.sort || 'rating');

// Watch filters and trigger Inertia reload
const updateFilters = () => {
    router.get(
        '/restaurants',
        {
            search: search.value || undefined,
            cuisine: cuisine.value || undefined,
            offers_delivery: offersDelivery.value ? 'true' : undefined,
            offers_pickup: offersPickup.value ? 'true' : undefined,
            sort: sort.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([cuisine, offersDelivery, offersPickup, sort], updateFilters);

// Debounce search
let searchTimeout: any;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateFilters, 300);
});

const formatPrice = (priceInCents: number) => {
    if (priceInCents === 0) return 'Free';
    return (priceInCents / 100).toLocaleString('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Restaurants" />

        <div class="mx-auto max-w-7xl px-6 py-12 md:px-8">
            <!-- Title & Count -->
            <div class="mb-10 text-center sm:text-left">
                <h1 class="text-3xl font-extrabold tracking-tight">
                    Restaurants in {{ activeCity.name }}
                </h1>
                <p class="mt-2 text-sm font-medium text-muted-foreground">
                    Discover {{ restaurants.length }} local dining spots
                    offering delivery or pickup.
                </p>
            </div>

            <!-- Filters Bar -->
            <div class="mb-12 flex flex-col gap-6 rounded-xl bg-card p-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <!-- Search Input -->
                    <div class="relative md:col-span-2">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3"
                        >
                            <svg
                                class="h-4 w-4 text-muted-foreground"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by restaurant name or cuisine..."
                            class="w-full rounded-lg border-0 bg-muted py-2.5 pr-4 pl-9 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                        />
                    </div>

                    <!-- Cuisine Select -->
                    <div>
                        <select
                            v-model="cuisine"
                            class="w-full rounded-lg border-0 bg-muted px-3 py-2.5 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                        >
                            <option value="">All Cuisines</option>
                            <option
                                v-for="type in cuisineTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                    </div>

                    <!-- Sort Select -->
                    <div>
                        <select
                            v-model="sort"
                            class="w-full border-0 bg-muted px-3 py-2.5 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                        >
                            <option value="rating">Sort by Rating</option>
                            <option value="delivery_fee">
                                Sort by Delivery Fee
                            </option>
                            <option value="time">Sort by Speed</option>
                            <option value="name">Sort by Name</option>
                        </select>
                    </div>
                </div>

                <!-- Toggles -->
                <div
                    class="flex flex-wrap items-center gap-6 border-t border-border pt-4 text-xs font-semibold text-secondary-foreground"
                >
                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            v-model="offersDelivery"
                            type="checkbox"
                            class="h-4 w-4 rounded border-border text-foreground focus:ring-zinc-900"
                        />
                        <span>Offers Delivery</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2">
                        <input
                            v-model="offersPickup"
                            type="checkbox"
                            class="h-4 w-4 rounded border-border text-foreground focus:ring-zinc-900"
                        />
                        <span>Offers Pickup</span>
                    </label>
                </div>
            </div>

            <!-- Restaurant Grid -->
            <div
                v-if="restaurants.length > 0"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="restaurant in restaurants"
                    :key="restaurant.id"
                    :href="`/restaurants/${restaurant.slug}`"
                    class="group flex flex-col overflow-hidden rounded-xl bg-card transition-all hover:bg-muted dark:hover:bg-zinc-800"
                >
                    <!-- Restaurant Image -->
                    <div
                        class="relative aspect-video w-full overflow-hidden bg-muted"
                    >
                        <button
                            v-if="$page.props.auth.user"
                            @click.stop.prevent="toggleFavorite(restaurant.id)"
                            class="absolute top-3 right-3 z-10 rounded-full bg-background/80 p-1.5 text-muted-foreground transition-colors hover:bg-background hover:text-red-500"
                            :class="{
                                'text-red-500': favoriteIds.includes(
                                    restaurant.id,
                                ),
                            }"
                        >
                            <HugeiconsIcon
                                :icon="HeartIcon"
                                class="h-4.5 w-4.5"
                                :class="{
                                    'fill-red-500 text-red-500':
                                        favoriteIds.includes(restaurant.id),
                                }"
                            />
                        </button>
                        <img
                            v-if="restaurant.image_path"
                            :src="restaurant.image_url"
                            :alt="restaurant.name"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-muted-foreground"
                        >
                            <!-- Store icon placeholder -->
                            <svg
                                class="h-10 w-10"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72M6.75 18h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3
                                    class="text-lg font-bold transition-colors group-hover:text-zinc-600 dark:group-hover:text-zinc-300"
                                >
                                    {{ restaurant.name }}
                                </h3>
                                <p
                                    class="mt-1 text-xs font-semibold text-muted-foreground"
                                >
                                    {{ restaurant.cuisine_type }}
                                </p>
                            </div>
                            <div
                                class="flex items-center gap-1 bg-muted px-2 py-1 text-xs font-bold"
                            >
                                <svg
                                    class="h-3 w-3 fill-amber-500 text-amber-500"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                    />
                                </svg>
                                <span>{{
                                    restaurant.rating > 0
                                        ? restaurant.rating.toFixed(1)
                                        : 'New'
                                }}</span>
                            </div>
                        </div>

                        <!-- Details (Delivery, Pickup, Time, Fee) -->
                        <div
                            class="mt-6 flex items-center justify-between border-t border-border pt-4 text-xs font-medium text-muted-foreground"
                        >
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
                                    }}m</strong
                                >
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl bg-card p-8 py-20 text-center">
                <p class="font-semibold text-muted-foreground">
                    No restaurants found matching your filters.
                </p>
                <button
                    @click="
                        search = '';
                        cuisine = '';
                        offersDelivery = false;
                        offersPickup = false;
                    "
                    class="mt-4 inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-bold text-primary-foreground focus:outline-none"
                >
                    Reset Filters
                </button>
            </div>
        </div>
    </CustomerLayout>
</template>
