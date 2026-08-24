<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    Location01Icon,
    Delete02Icon,
    PencilEdit02Icon,
    PlusSignIcon,
} from '@hugeicons/core-free-icons';
import AddressDialog from '@/components/AddressDialog.vue';
import SavedAddressController from '@/actions/App/Http/Controllers/SavedAddressController';
import { Button } from '@/components/ui/button';

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

defineProps<{
    savedAddresses: SavedAddress[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Saved Addresses',
                href: '/dashboard/addresses',
            },
        ],
    },
});

const isAddressDialogOpen = ref(false);
const selectedAddress = ref<SavedAddress | null>(null);

const openAddressDialog = (address: SavedAddress | null = null) => {
    selectedAddress.value = address;
    isAddressDialogOpen.value = true;
};

const deleteAddress = (id: number) => {
    if (confirm('Are you sure you want to delete this address?')) {
        router.delete(SavedAddressController.destroy.url(id));
    }
};
</script>

<template>
    <Head title="Saved Addresses" />

    <AddressDialog
        v-model:open="isAddressDialogOpen"
        :address="selectedAddress"
    />

    <div class="mx-auto w-full max-w-5xl space-y-10 px-6 py-8 md:px-8">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">
                    Saved Addresses
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage your delivery locations to expedite checkouts on your
                    future orders.
                </p>
            </div>
            <Button
                variant="outline"
                size="sm"
                class="self-start text-xs font-bold sm:self-auto"
                @click="openAddressDialog(null)"
            >
                <HugeiconsIcon :icon="PlusSignIcon" class="mr-1 h-3.5 w-3.5" />
                Add Address
            </Button>
        </div>

        <!-- Saved Addresses Grid -->
        <div class="space-y-4">
            <div
                v-if="savedAddresses.length === 0"
                class="rounded-xl bg-muted/10 p-8 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    You don't have any saved addresses yet.
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    class="mt-4 text-xs font-bold"
                    @click="openAddressDialog(null)"
                >
                    <HugeiconsIcon
                        :icon="PlusSignIcon"
                        class="mr-1 h-3.5 w-3.5"
                    />
                    Add your first address
                </Button>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="address in savedAddresses"
                    :key="address.id"
                    class="flex animate-in items-start justify-between gap-4 rounded-xl bg-muted/40 p-6 duration-300 fade-in"
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

                    <div class="flex gap-1.5">
                        <button
                            class="p-1 text-muted-foreground transition-colors hover:text-foreground"
                            @click="openAddressDialog(address)"
                            title="Edit Address"
                        >
                            <HugeiconsIcon
                                :icon="PencilEdit02Icon"
                                class="h-4 w-4"
                            />
                        </button>
                        <button
                            class="p-1 text-muted-foreground transition-colors hover:text-destructive"
                            @click="deleteAddress(address.id)"
                            title="Delete Address"
                        >
                            <HugeiconsIcon
                                :icon="Delete02Icon"
                                class="h-4 w-4"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
