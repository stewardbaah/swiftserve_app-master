<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    Calendar01Icon,
    Location01Icon,
    ArrowRight01Icon,
    ShoppingBag01Icon,
    CreditCardIcon,
} from '@hugeicons/core-free-icons';
import DashboardController, {
    orders,
    addresses,
} from '@/actions/App/Http/Controllers/DashboardController';
import { track } from '@/routes/orders';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface SavedAddress {
    id: number;
    user_id: number;
    label: string;
    address_line1: string;
    address_line2: string | null;
    city: string;
    state: string;
    postal_code: string;
}

interface MenuItem {
    id: number;
    name: string;
    price: number;
}

interface OrderItem {
    id: number;
    quantity: number;
    price: number;
    notes: string | null;
    customization: any;
    menu_item: MenuItem;
}

interface Restaurant {
    id: number;
    name: string;
    cuisine_type: string;
    address: string;
}

interface Review {
    id: number;
    rating: number;
    comment: string | null;
}

interface Complaint {
    id: number;
    reason: string;
    details: string;
    status: string;
    resolution_comment: string | null;
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    type: string;
    delivery_address: string | null;
    subtotal: number;
    delivery_fee: number;
    service_fee: number;
    tax: number;
    discount: number;
    total: number;
    special_instructions: string | null;
    estimated_ready_time: string | null;
    payment_status: string;
    payment_method: string;
    promo_code: string | null;
    created_at: string;
    restaurant: Restaurant;
    order_items?: OrderItem[];
    review?: Review | null;
    complaint?: Complaint | null;
}

defineProps<{
    activeOrders: Order[];
    pastOrders: Order[];
    savedAddresses: SavedAddress[];
    stats: {
        totalOrdersCount: number;
        totalSpent: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
        ],
    },
});

const page = usePage();
const user = computed(
    () => page.props.auth.user as { name: string; email: string },
);

const reorderOrder = (id: number) => {
    router.post(DashboardController.reorder.url(id));
};

const formatPrice = (priceInCents: number) => {
    return (priceInCents / 100).toLocaleString('en-GH', {
        style: 'currency',
        currency: 'GHS',
    });
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-GH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusBadgeVariant = (
    status: string,
): 'secondary' | 'outline' | 'default' | 'destructive' => {
    switch (status) {
        case 'received':
            return 'secondary';
        case 'preparing':
            return 'outline';
        case 'ready_for_pickup':
        case 'out_for_delivery':
            return 'default';
        case 'completed':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'default';
    }
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        received: 'Order Received',
        preparing: 'Preparing',
        ready_for_pickup: 'Ready for Pickup',
        out_for_delivery: 'Out for Delivery',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    return labels[status] || status.toUpperCase();
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto w-full max-w-5xl space-y-10 px-6 py-8 md:px-8">
        <!-- Header Welcome & Lifetime Stats (whitespace prioritized, no shadow, background differentiation) -->
        <div class="rounded-xl bg-muted/40 p-8">
            <h1 class="text-2xl font-extrabold tracking-tight">
                Welcome back, {{ user.name }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage your orders, saved delivery addresses, and payment
                history.
            </p>

            <div
                class="mt-8 grid grid-cols-2 gap-8 border-t border-border/30 pt-6"
            >
                <div>
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Lifetime Orders</span
                    >
                    <h3 class="mt-1 text-2xl font-bold">
                        {{ stats.totalOrdersCount }}
                    </h3>
                </div>
                <div>
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Lifetime Spent</span
                    >
                    <h3 class="mt-1 text-2xl font-bold">
                        {{ formatPrice(stats.totalSpent) }}
                    </h3>
                </div>
            </div>
        </div>

        <!-- Active Orders Section -->
        <div id="active-orders" class="space-y-4">
            <h2 class="text-lg font-bold tracking-tight">Active Orders</h2>

            <div
                v-if="activeOrders.length === 0"
                class="rounded-xl bg-muted/10 p-8 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    You don't have any active orders right now.
                </p>
                <Link
                    href="/restaurants"
                    class="mt-4 inline-block text-xs font-bold text-primary underline underline-offset-4 hover:text-primary/80"
                >
                    Order some food
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="order in activeOrders"
                    :key="order.id"
                    class="flex flex-col items-start justify-between gap-6 rounded-xl bg-muted/40 p-6 md:flex-row md:items-center"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-bold">{{
                                order.restaurant.name
                            }}</span>
                            <Badge
                                :variant="getStatusBadgeVariant(order.status)"
                            >
                                {{ getStatusLabel(order.status) }}
                            </Badge>
                        </div>
                        <p class="text-xs font-semibold text-muted-foreground">
                            Order #{{ order.order_number }} &middot;
                            {{ formatPrice(order.total) }}
                        </p>
                        <div
                            class="mt-2 flex items-center gap-2 border-t border-border/20 pt-2 text-xs text-muted-foreground"
                        >
                            <HugeiconsIcon
                                :icon="CreditCardIcon"
                                class="h-3.5 w-3.5 text-muted-foreground/75"
                            />
                            <span
                                >Payment:
                                {{
                                    order.payment_status === 'paid'
                                        ? 'Paid'
                                        : 'Pending'
                                }}
                                via {{ order.payment_method }}</span
                            >
                        </div>
                    </div>

                    <Link
                        :href="track.url(order.id)"
                        class="inline-flex items-center justify-center gap-1.5 self-stretch rounded-lg bg-primary px-4 py-2 text-xs font-bold text-primary-foreground transition-all hover:opacity-90 md:self-auto"
                    >
                        <span>Track Order</span>
                        <HugeiconsIcon
                            :icon="ArrowRight01Icon"
                            class="h-3.5 w-3.5"
                        />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Recent Orders (Summary) -->
        <div class="space-y-4">
            <div class="flex items-end justify-between">
                <h2 class="text-lg font-bold tracking-tight">Recent Orders</h2>
                <Link
                    v-if="pastOrders.length > 0"
                    :href="orders.url()"
                    class="text-xs font-bold text-primary underline-offset-4 hover:underline"
                >
                    View all orders
                </Link>
            </div>

            <div
                v-if="pastOrders.length === 0"
                class="rounded-xl bg-muted/10 p-8 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No orders in your history yet.
                </p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="order in pastOrders"
                    :key="order.id"
                    class="flex flex-col items-start justify-between gap-6 rounded-xl bg-muted/40 p-6 md:flex-row md:items-center"
                >
                    <div class="flex-1 space-y-1.5">
                        <div class="flex items-center gap-3">
                            <span class="font-bold">{{
                                order.restaurant.name
                            }}</span>
                            <span
                                class="flex items-center gap-1 text-xs font-medium text-muted-foreground"
                            >
                                <HugeiconsIcon
                                    :icon="Calendar01Icon"
                                    class="h-3 w-3 text-muted-foreground/75"
                                />
                                {{ formatDate(order.created_at) }}
                            </span>
                            <Badge
                                v-if="order.review"
                                variant="secondary"
                                class="border-0 bg-amber-500/10 text-[10px] text-amber-500 hover:bg-amber-500/10"
                            >
                                ★ {{ order.review.rating }} Rated
                            </Badge>
                        </div>
                        <p class="text-xs font-medium text-muted-foreground">
                            <span v-if="order.order_items">
                                {{
                                    order.order_items
                                        .map(
                                            (item) =>
                                                `${item.quantity}x ${item.menu_item.name}`,
                                        )
                                        .join(', ')
                                }}
                            </span>
                        </p>
                        <div
                            class="flex items-center gap-4 pt-1 text-xs font-semibold text-muted-foreground"
                        >
                            <span>Total: {{ formatPrice(order.total) }}</span>
                            <span>&middot;</span>
                            <span class="flex items-center gap-1">
                                <HugeiconsIcon
                                    :icon="CreditCardIcon"
                                    class="h-3 w-3"
                                />
                                Paid via {{ order.payment_method }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex w-full items-center justify-end gap-3 self-stretch md:w-auto md:self-auto"
                    >
                        <Link
                            v-if="!order.review && order.status === 'completed'"
                            :href="track.url(order.id)"
                            class="text-xs font-bold text-muted-foreground underline underline-offset-4 hover:text-foreground"
                        >
                            Write Review
                        </Link>
                        <Button
                            variant="outline"
                            size="sm"
                            class="text-xs font-bold"
                            @click="reorderOrder(order.id)"
                        >
                            <HugeiconsIcon
                                :icon="ShoppingBag01Icon"
                                class="mr-1 h-3.5 w-3.5"
                            />
                            Reorder
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saved Addresses (Summary) -->
        <div class="space-y-4">
            <div class="flex items-end justify-between">
                <h2 class="text-lg font-bold tracking-tight">
                    Saved Addresses
                </h2>
                <Link
                    v-if="savedAddresses.length > 0"
                    :href="addresses.url()"
                    class="text-xs font-bold text-primary underline-offset-4 hover:underline"
                >
                    Manage addresses
                </Link>
            </div>

            <div
                v-if="savedAddresses.length === 0"
                class="rounded-xl bg-muted/10 p-8 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    You don't have any saved addresses yet.
                </p>
                <Link
                    :href="addresses.url()"
                    class="mt-4 inline-block text-xs font-bold text-primary underline underline-offset-4 hover:text-primary/80"
                >
                    Add an address
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="address in savedAddresses"
                    :key="address.id"
                    class="flex items-start justify-between gap-4 rounded-xl bg-muted/40 p-6"
                >
                    <div class="space-y-1 text-sm">
                        <div class="flex items-center gap-2">
                            <HugeiconsIcon
                                :icon="Location01Icon"
                                class="h-4 w-4 text-muted-foreground"
                            />
                            <span class="font-bold text-foreground">{{
                                address.label
                            }}</span>
                        </div>
                        <p
                            class="pt-1 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ address.address_line1
                            }}<span v-if="address.address_line2"
                                >, {{ address.address_line2 }}</span
                            ><br />
                            {{ address.city }}, {{ address.state }}
                            {{ address.postal_code }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
