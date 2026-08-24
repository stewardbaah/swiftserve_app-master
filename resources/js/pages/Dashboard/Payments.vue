<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    Calendar01Icon,
    CreditCardIcon,
    ShoppingBag01Icon,
} from '@hugeicons/core-free-icons';
import { Badge } from '@/components/ui/badge';

interface Restaurant {
    id: number;
    name: string;
}

interface Transaction {
    id: number;
    order_number: string;
    status: string;
    total: number;
    payment_status: string;
    payment_method: string;
    created_at: string;
    restaurant: Restaurant;
}

interface PaginatedTransactions {
    data: Transaction[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    last_page: number;
}

defineProps<{
    transactions: PaginatedTransactions;
    stats: {
        totalOrdersCount: number;
        totalSpent: number;
        averageSpent: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Payments',
                href: '/dashboard/payments',
            },
        ],
    },
});

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

const getPaymentStatusVariant = (
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status) {
        case 'paid':
            return 'secondary';
        case 'pending':
            return 'outline';
        case 'failed':
            return 'destructive';
        default:
            return 'default';
    }
};

const getPaymentStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        paid: 'Paid',
        pending: 'Pending',
        failed: 'Failed',
    };
    return labels[status] || status.toUpperCase();
};
</script>

<template>
    <Head title="Payments" />

    <div class="mx-auto w-full max-w-5xl space-y-10 px-6 py-8 md:px-8">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight">
                Payments & Transactions
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                View your billing details, past transactions, and track your
                lifetime spendings.
            </p>
        </div>

        <!-- Payment Statistics (flat background, priority on whitespace) -->
        <div class="rounded-xl bg-muted/40 p-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <div>
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Lifetime Spent</span
                    >
                    <h3 class="mt-1 text-2xl font-bold text-foreground">
                        {{ formatPrice(stats.totalSpent) }}
                    </h3>
                </div>
                <div
                    class="border-t border-border/30 pt-6 md:border-t-0 md:border-l md:pt-0 md:pl-8"
                >
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Successful Orders</span
                    >
                    <h3 class="mt-1 text-2xl font-bold text-foreground">
                        {{ stats.totalOrdersCount }}
                    </h3>
                </div>
                <div
                    class="border-t border-border/30 pt-6 md:border-t-0 md:border-l md:pt-0 md:pl-8"
                >
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Average Order Value</span
                    >
                    <h3 class="mt-1 text-2xl font-bold text-foreground">
                        {{ formatPrice(stats.averageSpent) }}
                    </h3>
                </div>
            </div>
        </div>

        <!-- Transactions History -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold tracking-tight">
                Transaction History
            </h2>

            <div
                v-if="transactions.data.length === 0"
                class="rounded-xl bg-muted/10 p-8 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No transactions recorded yet.
                </p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="transaction in transactions.data"
                    :key="transaction.id"
                    class="flex flex-col items-start justify-between gap-6 rounded-xl bg-muted/40 p-6 md:flex-row md:items-center"
                >
                    <div class="flex-1 space-y-1.5">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-bold text-foreground">{{
                                transaction.restaurant.name
                            }}</span>
                            <span
                                class="flex items-center gap-1 text-xs font-medium text-muted-foreground"
                            >
                                <HugeiconsIcon
                                    :icon="Calendar01Icon"
                                    class="h-3 w-3 text-muted-foreground/75"
                                />
                                {{ formatDate(transaction.created_at) }}
                            </span>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-semibold text-muted-foreground"
                        >
                            <span class="flex items-center gap-1">
                                <HugeiconsIcon
                                    :icon="ShoppingBag01Icon"
                                    class="h-3.5 w-3.5"
                                />
                                Order #{{ transaction.order_number }}
                            </span>
                            <span>&middot;</span>
                            <span class="flex items-center gap-1">
                                <HugeiconsIcon
                                    :icon="CreditCardIcon"
                                    class="h-3.5 w-3.5"
                                />
                                {{ transaction.payment_method }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex w-full items-center justify-between gap-6 self-stretch border-t border-border/20 pt-4 md:w-auto md:justify-end md:self-auto md:border-0 md:pt-0"
                    >
                        <div class="flex items-center gap-3">
                            <Badge
                                :variant="
                                    getPaymentStatusVariant(
                                        transaction.payment_status,
                                    )
                                "
                            >
                                {{
                                    getPaymentStatusLabel(
                                        transaction.payment_status,
                                    )
                                }}
                            </Badge>
                        </div>
                        <span
                            class="text-base font-bold text-foreground md:min-w-[100px] md:text-right"
                        >
                            {{ formatPrice(transaction.total) }}
                        </span>
                    </div>
                </div>

                <!-- Pagination Links -->
                <div
                    v-if="transactions.links && transactions.links.length > 3"
                    class="mt-8 flex justify-center gap-1.5"
                >
                    <Component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, index) in transactions.links"
                        :key="index"
                        :href="link.url || ''"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="{
                            'bg-primary text-primary-foreground': link.active,
                            'bg-muted/40 text-muted-foreground hover:bg-muted/65':
                                !link.active && link.url,
                            'cursor-not-allowed text-muted-foreground/40':
                                !link.url,
                        }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
