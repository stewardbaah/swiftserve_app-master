<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { login, register, dashboard } from '@/routes';

const page = usePage();
const activeCity = computed(
    () =>
        page.props.activeCity as {
            name: string;
            state: string;
            slug: string;
        } | null,
);
const cart = computed(() => page.props.cart as any);

const cartItemsCount = computed(() => {
    return (
        cart.value?.items?.reduce(
            (sum: number, item: any) => sum + item.quantity,
            0,
        ) || 0
    );
});

const formattedTotal = computed(() => {
    if (!cart.value?.total) return 'GHS 0.00';
    return (cart.value.total / 100).toLocaleString('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });
});

const dashboardUrl = computed(() => {
    return page.props.currentTeam
        ? dashboard((page.props.currentTeam as any).slug).url
        : '/';
});
</script>

<template>
    <div
        v-if="$page.props.auth.user"
        class="flex min-h-screen flex-col bg-background text-foreground transition-colors duration-200"
    >
        <!-- Main Navbar -->
        <header
            class="sticky top-0 z-40 w-full bg-background/80 backdrop-blur-md transition-colors duration-200"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8"
            >
                <!-- Left: Branding -->
                <div class="flex items-center gap-6">
                    <Link
                        href="/"
                        class="flex items-center gap-2 text-lg font-bold tracking-tight"
                    >
                        <svg
                            class="h-5 w-5 text-foreground"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
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
                        <span>SwiftServe</span>
                    </Link>

                    <!-- Active City Selector / Display -->
                    <div
                        v-if="activeCity"
                        class="hidden items-center gap-1.5 rounded-md bg-muted px-3 py-1.5 text-xs font-semibold text-secondary-foreground sm:flex"
                    >
                        <span>{{ activeCity.name }}</span>
                        <Link
                            href="/"
                            class="text-muted-foreground underline hover:text-foreground/80"
                            >Change</Link
                        >
                    </div>
                </div>

                <!-- Right: Cart & Auth Navigation -->
                <div class="flex items-center gap-4">
                    <!-- Cart Indicator -->
                    <Link
                        v-if="cartItemsCount > 0"
                        href="/checkout"
                        class="flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition-all hover:opacity-90"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                            />
                        </svg>
                        <span
                            >{{ cartItemsCount }}
                            {{ cartItemsCount === 1 ? 'item' : 'items' }} ({{
                                formattedTotal
                            }})</span
                        >
                    </Link>

                    <nav class="flex items-center gap-3">
                        <Link
                            v-if="activeCity && $page.url !== '/restaurants'"
                            href="/restaurants"
                            class="px-2 py-1 text-xs font-semibold hover:text-foreground/80"
                        >
                            Restaurants
                        </Link>

                        <!-- Dashboard / Authenticated Actions -->
                        <Link
                            :href="dashboardUrl"
                            class="px-2 py-1 text-xs font-semibold hover:text-foreground/80"
                        >
                            Dashboard
                        </Link>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main View Area -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="w-full bg-card py-12 transition-colors duration-200">
            <div
                class="mx-auto max-w-7xl px-6 text-center text-xs font-medium text-muted-foreground md:px-8"
            >
                <p>&copy; {{ new Date().getFullYear() }} SwiftServe App.</p>
                <div class="mt-4 flex justify-center gap-6">
                    <a href="/merchant" class="hover:text-foreground/80"
                        >Merchant Dashboard Login</a
                    >
                    <span>&middot;</span>
                    <a href="#" class="hover:text-foreground/80"
                        >Privacy Policy</a
                    >
                    <span>&middot;</span>
                    <a href="#" class="hover:text-foreground/80"
                        >Terms of Service</a
                    >
                </div>
            </div>
        </footer>
    </div>

    <!-- Simple Page for Unauthenticated Users -->
    <div
        v-else
        class="flex min-h-screen flex-col bg-background text-foreground transition-colors duration-200"
    >
        <!-- Simple Navbar -->
        <header
            class="w-full border-b border-border bg-card transition-colors duration-200"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 md:px-8"
            >
                <!-- Branding -->
                <Link
                    href="/"
                    class="flex items-center gap-2 text-lg font-bold tracking-tight"
                >
                    <svg
                        class="h-5 w-5 text-foreground"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
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
                    <span>SwiftServe</span>
                </Link>

                <!-- Simple Auth Navigation -->
                <nav class="flex items-center gap-3">
                    <Link
                        :href="login().url"
                        class="px-2 py-1 text-xs font-semibold hover:text-foreground/80"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register().url"
                        class="rounded-md bg-muted px-3 py-1.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
                    >
                        Register
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Main View Area -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Simple Footer -->
        <footer
            class="mt-auto w-full border-t border-border bg-background py-12 transition-colors duration-200"
        >
            <div
                class="mx-auto max-w-7xl px-6 text-center text-xs font-medium text-muted-foreground md:px-8"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }} SwiftServe. All rights
                    reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
