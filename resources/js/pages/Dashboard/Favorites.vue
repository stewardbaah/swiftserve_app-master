<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { HeartIcon, Store01Icon } from '@hugeicons/core-free-icons';

interface Restaurant {
    id: number;
    name: string;
    slug: string;
    cuisine_type: string;
    description: string | null;
    address: string;
    rating: number;
    image_path: string | null;
    image_url: string | null;
}

defineProps<{
    favorites: Restaurant[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Favorites',
                href: '/dashboard/favorites',
            },
        ],
    },
});

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
    <Head title="My Favorite Restaurants" />

    <div class="mx-auto w-full max-w-5xl space-y-10 px-6 py-8 md:px-8">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">
                Favorite Restaurants
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Your curated list of go-to dining spots. Quick-access menus and
                place orders.
            </p>
        </div>

        <!-- Favorites Grid -->
        <div class="space-y-4">
            <div
                v-if="favorites.length === 0"
                class="rounded-xl bg-muted/10 p-8 py-20 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    You haven't favorited any restaurants yet.
                </p>
                <Link
                    href="/restaurants"
                    class="mt-4 inline-block text-xs font-bold text-primary underline underline-offset-4 hover:text-primary/80"
                >
                    Browse restaurants
                </Link>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="restaurant in favorites"
                    :key="restaurant.id"
                    class="group relative flex flex-col overflow-hidden rounded-xl bg-muted/40 transition-colors duration-300 hover:bg-muted/60"
                >
                    <!-- Favorite Toggle Heart Overlay -->
                    <button
                        @click.stop.prevent="toggleFavorite(restaurant.id)"
                        class="absolute top-3 right-3 z-10 rounded-full bg-background/80 p-1.5 text-red-500 transition-colors hover:bg-background"
                        title="Remove from Favorites"
                    >
                        <HugeiconsIcon
                            :icon="HeartIcon"
                            class="h-4.5 w-4.5 fill-red-500 text-red-500"
                        />
                    </button>

                    <!-- Restaurant Image/Icon -->
                    <Link
                        :href="`/restaurants/${restaurant.slug}`"
                        class="relative flex aspect-video w-full items-center justify-center overflow-hidden bg-muted text-muted-foreground"
                    >
                        <img
                            v-if="restaurant.image_path"
                            :src="restaurant.image_url"
                            :alt="restaurant.name"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <HugeiconsIcon
                            v-else
                            :icon="Store01Icon"
                            class="h-10 w-10 text-muted-foreground/50"
                        />
                    </Link>

                    <!-- Info -->
                    <div class="flex flex-1 flex-col justify-between gap-4 p-5">
                        <div class="space-y-1">
                            <div class="flex items-start justify-between gap-2">
                                <Link
                                    :href="`/restaurants/${restaurant.slug}`"
                                    class="line-clamp-1 text-base font-bold text-foreground transition-colors hover:text-primary"
                                >
                                    {{ restaurant.name }}
                                </Link>
                                <div
                                    class="flex shrink-0 items-center gap-1 rounded bg-background px-2 py-0.5 text-xs font-bold text-foreground"
                                >
                                    <span class="text-amber-500">★</span>
                                    <span>{{
                                        restaurant.rating > 0
                                            ? restaurant.rating.toFixed(1)
                                            : 'New'
                                    }}</span>
                                </div>
                            </div>
                            <p
                                class="text-xs font-semibold text-muted-foreground"
                            >
                                {{ restaurant.cuisine_type }}
                            </p>
                            <p
                                v-if="restaurant.description"
                                class="line-clamp-2 pt-2 text-xs leading-relaxed text-muted-foreground/80"
                            >
                                {{ restaurant.description }}
                            </p>
                        </div>

                        <Link
                            :href="`/restaurants/${restaurant.slug}`"
                            class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-primary px-4 py-2 text-center text-xs font-bold text-primary-foreground transition-all hover:opacity-90"
                        >
                            Order Now
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
