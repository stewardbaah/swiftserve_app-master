<script setup lang="ts">
import { Head, Link, usePage, router, useHttp } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard, login, register } from '@/routes';
import { select, detect } from '@/routes/cities';
import { HugeiconsIcon } from '@hugeicons/vue';
import { HeartIcon } from '@hugeicons/core-free-icons';

interface City {
    id: number;
    name: string;
    state: string;
    slug: string;
    is_active: boolean;
}

interface Restaurant {
    id: number;
    name: string;
    slug: string;
    cuisine_type: string;
    description: string | null;
    delivery_fee: number;
    estimated_delivery_time: number;
    rating: number;
    image_path: string | null;
    image_url: string | null;
}

const props = defineProps<{
    cities: City[];
    activeCitySlug: string | null;
    activeCity: City | null;
    restaurants: Restaurant[];
    favoriteIds: number[];
}>();

const formatPrice = (priceInCents: number) => {
    if (priceInCents === 0) return 'Free';
    return (priceInCents / 100).toLocaleString('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });
};

const page = usePage();
const http = useHttp();
const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const searchQuery = ref('');
const isDetecting = ref(false);

const filteredCities = computed(() => {
    if (!searchQuery.value) return props.cities;
    const query = searchQuery.value.toLowerCase();
    return props.cities.filter(
        (city) =>
            city.name.toLowerCase().includes(query) ||
            city.state.toLowerCase().includes(query),
    );
});

const handleCitySelect = (citySlug: string) => {
    router.post(
        select().url,
        { city_slug: citySlug },
        { preserveScroll: true },
    );
};

const handleDetectLocation = async () => {
    isDetecting.value = true;
    try {
        await http.submit(detect());
        router.reload();
    } catch (error) {
        console.error('Failed to detect location:', error);
    } finally {
        isDetecting.value = false;
    }
};

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
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col bg-background text-foreground transition-colors duration-200"
    >
        <!-- Navigation Header -->
        <header class="w-full bg-card transition-colors duration-200">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 md:px-8"
            >
                <div class="flex items-center gap-3">
                    <svg
                        class="h-6 w-6 text-foreground"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <!-- HugeIcon Map Pin Point style -->
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    <span class="text-xl font-bold tracking-tight"
                        >SwiftServe</span
                    >
                </div>
                <nav class="flex items-center gap-6">
                    <a
                        v-if="!$page.props.auth.user"
                        href="/merchant"
                        class="text-sm font-medium hover:text-foreground/80"
                    >
                        Merchant Portal
                    </a>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboardUrl"
                        class="text-sm font-medium hover:text-foreground/80"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login().url"
                            class="text-sm font-medium hover:text-foreground/80"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register().url"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1">
            <section
                class="bg-card py-24 transition-colors duration-200 md:py-32"
            >
                <div class="mx-auto max-w-3xl px-6 text-center md:px-8">
                    <h1
                        class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl md:text-6xl"
                    >
                        Curated local flavors. Delivered to your door.
                    </h1>
                    <p
                        class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-muted-foreground md:text-xl"
                    >
                        Discover the best independent restaurants in your
                        community. Select your city to begin browsing menus.
                    </p>

                    <!-- Active City Selection Notification -->
                    <div
                        v-if="activeCity"
                        class="mt-8 inline-flex items-center gap-2 bg-muted px-4 py-2 text-sm font-medium"
                    >
                        <span
                            >Currently browsing
                            <strong
                                >{{ activeCity.name }},
                                {{ activeCity.state }}</strong
                            ></span
                        >
                        <Link
                            href="/restaurants"
                            class="ml-2 underline hover:text-foreground/80"
                        >
                            Go to Directory
                        </Link>
                    </div>

                    <!-- Search Input and Detect Location -->
                    <div
                        class="mx-auto mt-12 flex max-w-lg flex-col gap-3 sm:flex-row"
                    >
                        <div class="relative flex-1">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <svg
                                    class="h-5 w-5 text-muted-foreground"
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
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search for your city..."
                                class="w-full rounded-lg border-0 bg-muted py-3 pr-4 pl-10 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                            />
                        </div>
                        <button
                            @click="handleDetectLocation"
                            :disabled="isDetecting"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-muted px-6 py-3 text-sm font-medium text-foreground transition-colors hover:bg-secondary disabled:opacity-50"
                        >
                            <svg
                                class="h-4 w-4"
                                :class="{ 'animate-spin': isDetecting }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"
                                />
                            </svg>
                            {{
                                isDetecting
                                    ? 'Detecting...'
                                    : 'Detect My Location'
                            }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Cities Grid Section -->
            <section class="bg-background py-24 transition-colors duration-200">
                <div class="mx-auto max-w-7xl px-6 md:px-8">
                    <h2
                        class="mb-8 text-center text-xs font-bold tracking-widest text-muted-foreground uppercase"
                    >
                        Available Cities
                    </h2>

                    <div
                        class="mx-auto grid max-w-7xl grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5"
                    >
                        <button
                            v-for="city in filteredCities"
                            :key="city.id"
                            @click="handleCitySelect(city.slug)"
                            class="group rounded-xl bg-card px-6 py-3 text-left transition-colors hover:bg-muted focus:ring-2 focus:ring-ring focus:outline-none dark:hover:bg-zinc-800"
                        >
                            <div
                                class="text-center text-lg font-bold text-foreground"
                            >
                                {{ city.name }}
                            </div>
                            <!-- <div class="text-xs text-muted-foreground text-center font-medium group-hover:text-zinc-600 dark:group-hover:text-muted-foreground transition-colors mt-1">{{ city.state }}</div> -->
                        </button>
                    </div>

                    <div
                        v-if="filteredCities.length === 0"
                        class="mt-8 text-center text-sm text-muted-foreground"
                    >
                        No active cities found matching your search.
                    </div>
                </div>
            </section>

            <!-- Featured Restaurants Section -->
            <section class="bg-card py-24 transition-colors duration-200">
                <div class="mx-auto max-w-7xl px-6 md:px-8">
                    <div
                        class="mb-12 flex flex-col justify-between md:flex-row md:items-end"
                    >
                        <div>
                            <h2
                                class="mb-2 text-xs font-bold tracking-widest text-muted-foreground uppercase"
                            >
                                Featured Restaurants
                            </h2>
                            <h3
                                class="text-3xl font-extrabold tracking-tight text-foreground"
                            >
                                Popular spots in
                                {{ activeCity ? activeCity.name : 'your area' }}
                            </h3>
                        </div>
                        <Link
                            v-if="activeCity"
                            href="/restaurants"
                            class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-foreground hover:underline md:mt-0"
                        >
                            View All Restaurants &rarr;
                        </Link>
                    </div>

                    <div
                        v-if="restaurants.length > 0"
                        class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4"
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
                                    @click.stop.prevent="
                                        toggleFavorite(restaurant.id)
                                    "
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
                                                favoriteIds.includes(
                                                    restaurant.id,
                                                ),
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

                    <div
                        v-else
                        class="bg-background py-12 text-center text-muted-foreground"
                    >
                        <p v-if="activeCity">
                            No active restaurants found in
                            {{ activeCity.name }}.
                        </p>
                        <p v-else>
                            Please select a city above to see available
                            restaurants.
                        </p>
                    </div>

                    <div
                        class="mt-12 text-center"
                        v-if="!activeCity && cities.length > 0"
                    >
                        <p class="mb-3 text-sm text-muted-foreground">
                            Want to explore all of them?
                        </p>
                        <span class="text-sm text-muted-foreground"
                            >Select your city below to get started</span
                        >
                    </div>
                </div>
            </section>

            <!-- How it Works Section -->
            <section class="bg-card py-24 transition-colors duration-200">
                <div class="mx-auto max-w-7xl px-6 md:px-8">
                    <h2
                        class="mb-16 text-center text-2xl font-bold tracking-tight text-foreground"
                    >
                        Fresh food in three simple steps
                    </h2>

                    <div class="mx-auto grid max-w-5xl gap-12 md:grid-cols-3">
                        <div class="flex flex-col items-center text-center">
                            <div
                                class="mb-4 text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Step 01
                            </div>
                            <h3 class="mb-2 text-lg font-bold">
                                Select your City
                            </h3>
                            <p
                                class="max-w-xs text-sm leading-relaxed text-muted-foreground"
                            >
                                Choose or search for your city above to explore
                                restaurants serving your immediate neighborhood.
                            </p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div
                                class="mb-4 text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Step 02
                            </div>
                            <h3 class="mb-2 text-lg font-bold">
                                Build your Order
                            </h3>
                            <p
                                class="max-w-xs text-sm leading-relaxed text-muted-foreground"
                            >
                                Select from diverse menus and customize dishes
                                to match your taste and dietary preferences.
                            </p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div
                                class="mb-4 text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Step 03
                            </div>
                            <h3 class="mb-2 text-lg font-bold">
                                Track in Real-Time
                            </h3>
                            <p
                                class="max-w-xs text-sm leading-relaxed text-muted-foreground"
                            >
                                Place your order with simulated payments and
                                watch progress live from kitchen to delivery.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Merchant Partner Section -->
            <section v-if="!$page.props.auth.user" class="bg-muted py-24 transition-colors duration-200">
                <div class="mx-auto max-w-7xl px-6 text-center md:px-8">
                    <h2 class="text-3xl font-extrabold tracking-tight text-foreground">
                        Partner with SwiftServe
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-base text-muted-foreground">
                        Are you a local restaurant owner? Join SwiftServe to reach more customers, manage menus, and track incoming orders with our dedicated merchant dashboard.
                    </p>
                    <div class="mt-8">
                        <a
                            href="/merchant"
                            class="inline-flex items-center justify-center rounded-md bg-foreground px-6 py-3 text-sm font-medium text-background transition-colors hover:bg-foreground/90 focus:ring-2 focus:ring-ring focus:outline-none"
                        >
                            Merchant Portal
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer
            class="w-full bg-background py-12 transition-colors duration-200"
        >
            <div
                class="mx-auto max-w-7xl px-6 text-center text-xs text-muted-foreground md:px-8"
            >
                &copy; 2026 SwiftServe. All rights reserved.
            </div>
        </footer>
    </div>
</template>
