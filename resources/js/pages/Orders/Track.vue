<script setup lang="ts">
import { Head, router, useForm, usePoll } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';

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
    order_items: OrderItem[];
    review: Review | null;
    complaint: Complaint | null;
}

const props = defineProps<{
    order: Order;
}>();

// Status Mapping for display
const statusMap: Record<string, { label: string; step: number; desc: string }> =
    {
        received: {
            label: 'Order Received',
            step: 1,
            desc: 'We have received your order and are waiting for the restaurant to accept it.',
        },
        preparing: {
            label: 'Preparing',
            step: 2,
            desc: 'The kitchen is preparing your fresh meal.',
        },
        ready_for_pickup: {
            label: 'Ready for Pickup',
            step: 3,
            desc: 'Your meal is packaged and ready to be picked up!',
        },
        out_for_delivery: {
            label: 'Out for Delivery',
            step: 3,
            desc: 'A courier has picked up your order and is heading your way!',
        },
        completed: {
            label: 'Completed',
            step: 4,
            desc: 'Your order was successfully delivered / picked up. Enjoy!',
        },
        cancelled: {
            label: 'Cancelled',
            step: 0,
            desc: 'This order was cancelled.',
        },
    };

const currentStep = computed(() => {
    return statusMap[props.order.status]?.step ?? 1;
});

// Poll for updates to the order status
const { stop } = usePoll(5000, {
    only: ['order'],
}, {
    autoStart: props.order.status !== 'completed' && props.order.status !== 'cancelled',
});

// Watch for terminal order status to stop polling
watch(() => props.order.status, (newStatus) => {
    if (newStatus === 'completed' || newStatus === 'cancelled') {
        stop();
    }
});

// Review Form
const reviewForm = useForm({
    rating: 5,
    comment: '',
});

const submitReview = () => {
    reviewForm.post(`/orders/${props.order.id}/review`);
};

// Complaint Form
const complaintForm = useForm({
    reason: '',
    details: '',
});

const submitComplaint = () => {
    complaintForm.post(`/orders/${props.order.id}/complaint`, {
        onSuccess: () => {
            complaintForm.reset();
        },
    });
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
        <Head :title="`Track Order ${order.order_number}`" />

        <div class="mx-auto max-w-4xl space-y-12 px-6 py-12 md:px-8">
            <!-- Header Summary (Prioritizing whitespace, background sections, no shadows) -->
            <div
                class="flex flex-col justify-between gap-6 rounded-xl bg-card p-8 md:flex-row md:items-center"
            >
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">
                        Order {{ order.order_number }}
                    </h1>
                    <p class="mt-2 text-sm font-medium text-muted-foreground">
                        From
                        <strong>{{ order.restaurant.name }}</strong> &middot;
                        Placed
                        {{
                            new Date(order.created_at).toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit',
                            })
                        }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        href="/restaurants"
                        class="rounded-lg bg-primary px-4 py-2.5 text-center text-xs font-bold text-primary-foreground transition-all hover:opacity-90"
                    >
                        Order Something Else
                    </Link>
                </div>
            </div>

            <!-- Payment Pending Warning Banner -->
            <div
                v-if="order.payment_status === 'pending'"
                class="rounded-xl bg-amber-500/10 p-6 text-amber-600 dark:bg-amber-500/5 dark:text-amber-500"
            >
                <h3 class="text-sm font-bold">
                    Awaiting Payment Confirmation
                </h3>
                <p class="mt-1 text-xs leading-relaxed text-amber-600/90 dark:text-amber-500/90">
                    We are waiting for payment confirmation from Paystack. This page will automatically update once confirmed. Do not close this page.
                </p>
            </div>

            <!-- Live Status Tracker Block -->
            <div class="space-y-8 rounded-xl bg-card p-8">
                <div class="text-center md:text-left">
                    <h2 class="text-lg font-bold text-foreground">
                        {{ statusMap[order.status]?.label }}
                    </h2>
                    <p
                        class="mt-1 text-xs leading-relaxed text-muted-foreground"
                    >
                        {{ statusMap[order.status]?.desc }}
                    </p>
                </div>

                <!-- Tracker Steps (Visual Progress) -->
                <div
                    v-if="order.status !== 'cancelled'"
                    class="relative grid grid-cols-4 gap-4 pt-4"
                >
                    <!-- Progress Bar line -->
                    <div
                        class="absolute top-[26px] right-0 left-0 z-0 h-1 bg-muted"
                    ></div>
                    <div
                        class="absolute top-[26px] left-0 z-0 h-1 bg-zinc-950 transition-all duration-500 dark:bg-background"
                        :style="{
                            width: `${(Math.max(0, currentStep - 1) / 3) * 100}%`,
                        }"
                    ></div>

                    <!-- Step 1: Received -->
                    <div class="z-10 flex flex-col items-center text-center">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-[10px] font-extrabold"
                            :class="
                                currentStep >= 1
                                    ? 'border-zinc-950 bg-primary text-primary-foreground dark:border-zinc-50'
                                    : 'border-zinc-200 bg-muted text-muted-foreground dark:border-zinc-700'
                            "
                        >
                            1
                        </div>
                        <span
                            class="mt-2 text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                currentStep >= 1
                                    ? 'text-foreground'
                                    : 'text-muted-foreground'
                            "
                            >Received</span
                        >
                    </div>

                    <!-- Step 2: Preparing -->
                    <div class="z-10 flex flex-col items-center text-center">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-[10px] font-extrabold"
                            :class="
                                currentStep >= 2
                                    ? 'border-zinc-950 bg-primary text-primary-foreground dark:border-zinc-50'
                                    : 'border-zinc-200 bg-muted text-muted-foreground dark:border-zinc-700'
                            "
                        >
                            2
                        </div>
                        <span
                            class="mt-2 text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                currentStep >= 2
                                    ? 'text-foreground'
                                    : 'text-muted-foreground'
                            "
                            >Preparing</span
                        >
                    </div>

                    <!-- Step 3: Out/Ready -->
                    <div class="z-10 flex flex-col items-center text-center">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-[10px] font-extrabold"
                            :class="
                                currentStep >= 3
                                    ? 'border-zinc-950 bg-primary text-primary-foreground dark:border-zinc-50'
                                    : 'border-zinc-200 bg-muted text-muted-foreground dark:border-zinc-700'
                            "
                        >
                            3
                        </div>
                        <span
                            class="mt-2 text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                currentStep >= 3
                                    ? 'text-foreground'
                                    : 'text-muted-foreground'
                            "
                            >{{
                                order.type === 'pickup' ? 'Ready' : 'Transit'
                            }}</span
                        >
                    </div>

                    <!-- Step 4: Completed -->
                    <div class="z-10 flex flex-col items-center text-center">
                        <div
                            class="flex h-6 w-6 items-center justify-center rounded-full border-2 text-[10px] font-extrabold"
                            :class="
                                currentStep >= 4
                                    ? 'border-zinc-950 bg-primary text-primary-foreground dark:border-zinc-50'
                                    : 'border-zinc-200 bg-muted text-muted-foreground dark:border-zinc-700'
                            "
                        >
                            4
                        </div>
                        <span
                            class="mt-2 text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                currentStep >= 4
                                    ? 'text-foreground'
                                    : 'text-muted-foreground'
                            "
                            >Arrived</span
                        >
                    </div>
                </div>
            </div>

            <!-- Review block for Completed Order -->
            <div
                v-if="order.status === 'completed'"
                class="space-y-6 rounded-xl bg-card p-8"
            >
                <h2 class="text-lg font-bold">Review Your Meal</h2>

                <!-- If already reviewed -->
                <div
                    v-if="order.review"
                    class="space-y-2 text-sm font-semibold"
                >
                    <div class="flex items-center gap-1">
                        <span class="text-muted-foreground">Rating Given:</span>
                        <div class="flex gap-0.5">
                            <svg
                                v-for="i in order.review.rating"
                                :key="i"
                                class="h-4 w-4 fill-amber-500 text-amber-500"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        v-if="order.review.comment"
                        class="text-muted-foreground italic"
                    >
                        "{{ order.review.comment }}"
                    </div>
                </div>

                <!-- Review input form -->
                <form
                    v-else
                    @submit.prevent="submitReview"
                    class="space-y-4 text-sm font-semibold"
                >
                    <div class="space-y-2">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Rating</label
                        >
                        <div class="flex gap-2">
                            <button
                                v-for="score in 5"
                                :key="score"
                                type="button"
                                @click="reviewForm.rating = score"
                                class="rounded-md border border-border p-2 text-xs transition-colors focus:outline-none"
                                :class="
                                    reviewForm.rating === score
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-white text-muted-foreground'
                                "
                            >
                                {{ score }} Star{{ score === 1 ? '' : 's' }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >Your Experience</label
                        >
                        <textarea
                            v-model="reviewForm.comment"
                            placeholder="Tell us what you liked or how we can improve..."
                            class="w-full rounded-lg border-0 bg-muted p-3 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none"
                            rows="3"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="reviewForm.processing"
                        class="rounded-lg bg-primary px-6 py-2.5 text-xs font-bold text-primary-foreground transition-all hover:opacity-90 disabled:opacity-50"
                    >
                        Submit Review
                    </button>
                </form>
            </div>

            <!-- Complaint / Issue Section -->
            <div class="space-y-6 bg-card p-8 rounded-xl">
                <h2 class="text-lg font-bold text-foreground">Report an Issue / Complaint</h2>

                <!-- If already submitted a complaint -->
                <div v-if="order.complaint" class="space-y-4 text-xs font-semibold text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <span class="font-bold uppercase tracking-wider">Reason:</span>
                        <span class="text-foreground font-normal">{{ order.complaint.reason }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold uppercase tracking-wider">Status:</span>
                        <span class="font-bold uppercase tracking-wider" :class="{
                            'text-amber-500': order.complaint.status === 'pending',
                            'text-green-600 dark:text-green-500': order.complaint.status === 'resolved',
                            'text-red-500': order.complaint.status === 'dismissed'
                        }">{{ order.complaint.status.toUpperCase() }}</span>
                    </div>
                    <div class="leading-relaxed">
                        <span class="font-bold uppercase tracking-wider block mb-1">Details:</span>
                        <p class="text-foreground font-normal bg-muted p-4 rounded-lg leading-relaxed">{{ order.complaint.details }}</p>
                    </div>
                    <div v-if="order.complaint.resolution_comment" class="mt-4 border-t border-border pt-4">
                        <span class="font-bold uppercase tracking-wider block text-foreground mb-1">Merchant Response:</span>
                        <p class="text-foreground font-normal italic bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 p-4 rounded-lg leading-relaxed">{{ order.complaint.resolution_comment }}</p>
                    </div>
                </div>

                <!-- Complaint Form -->
                <form v-else @submit.prevent="submitComplaint" class="space-y-4 text-xs font-semibold">
                    <div class="space-y-2">
                        <label class="font-bold tracking-wider text-muted-foreground uppercase">Reason for Issue</label>
                        <select v-model="complaintForm.reason" class="w-full rounded-lg border-0 bg-muted p-3 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none">
                            <option value="" disabled>Select a reason...</option>
                            <option value="Missing Items">Missing Items</option>
                            <option value="Cold or Stale Food">Cold or Stale Food</option>
                            <option value="Late Delivery">Late Delivery</option>
                            <option value="Incorrect Order">Incorrect Order</option>
                            <option value="Other">Other (provide details below)</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="font-bold tracking-wider text-muted-foreground uppercase">Details</label>
                        <textarea v-model="complaintForm.details" placeholder="Please describe the problem in detail..." class="w-full rounded-lg border-0 bg-muted p-3 text-sm text-foreground placeholder-zinc-400 focus:ring-2 focus:ring-ring focus:outline-none" rows="3"></textarea>
                    </div>

                    <button type="submit" :disabled="complaintForm.processing || !complaintForm.reason || !complaintForm.details" class="rounded-lg bg-primary px-6 py-2.5 text-xs font-bold text-primary-foreground transition-all hover:opacity-90 disabled:opacity-50">
                        Submit Complaint
                    </button>
                </form>
            </div>

            <!-- Receipts / Order Items list details -->
            <div class="space-y-6 rounded-xl bg-card p-8">
                <h2 class="border-b border-border pb-3 text-lg font-bold">
                    Receipt
                </h2>

                <div class="space-y-4">
                    <div
                        v-for="item in order.order_items"
                        :key="item.id"
                        class="flex justify-between text-sm font-semibold"
                    >
                        <div>
                            <span
                                >{{ item.quantity }}x
                                {{ item.menu_item.name }}</span
                            >
                            <div
                                v-if="item.notes"
                                class="mt-0.5 text-xs font-medium text-muted-foreground"
                            >
                                "{{ item.notes }}"
                            </div>
                        </div>
                        <span>{{
                            formatPrice(item.price * item.quantity)
                        }}</span>
                    </div>
                </div>

                <!-- Totals -->
                <div
                    class="space-y-2 border-t border-border pt-6 text-xs font-semibold text-muted-foreground"
                >
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>{{ formatPrice(order.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Delivery Fee</span>
                        <span>{{ formatPrice(order.delivery_fee) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Service Fee</span>
                        <span>{{ formatPrice(order.service_fee) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tax ({{ order.subtotal > 0 ? Math.round((order.tax / order.subtotal) * 100) : 0 }}%)</span>
                        <span>{{ formatPrice(order.tax) }}</span>
                    </div>
                    <div
                        v-if="order.discount > 0"
                        class="flex justify-between text-green-600"
                    >
                        <span>Discount</span>
                        <span>-{{ formatPrice(order.discount) }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-border pt-2 text-sm font-bold text-foreground"
                    >
                        <span>Total Paid</span>
                        <span>{{ formatPrice(order.total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
