<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SavedAddressController from '@/actions/App/Http/Controllers/SavedAddressController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { ref, watch, nextTick, onUnmounted } from 'vue';

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
    open: boolean;
    address: SavedAddress | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
}>();

const form = useForm({
    label: '',
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    postal_code: '',
    latitude: '',
    longitude: '',
});

const mapContainer = ref<HTMLDivElement | null>(null);
let map: any = null;
let marker: any = null;
const isLocating = ref(false);

const destroyMap = () => {
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
};

onUnmounted(() => {
    destroyMap();
});

const reverseGeocode = async (lat: number, lng: number) => {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18`, {
            headers: {
                'Accept-Language': 'en'
            }
        });
        if (response.ok) {
            const data = await response.json();
            if (data && data.address) {
                const addr = data.address;
                const street = addr.road || addr.suburb || addr.neighbourhood || '';
                const houseNumber = addr.house_number ? addr.house_number + ' ' : '';
                form.address_line1 = houseNumber + street;
                form.city = addr.city || addr.town || addr.village || addr.municipality || '';
                form.state = addr.state || addr.region || '';
                form.postal_code = addr.postcode || '';
            }
        }
    } catch (e) {
        console.error('Reverse geocoding error:', e);
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

            if (map && marker) {
                const L = (window as any).L;
                const newPos = new L.LatLng(lat, lng);
                marker.setLatLng(newPos);
                map.setView(newPos, 15);
            }
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

            const lat = form.latitude ? parseFloat(form.latitude) : defaultLat;
            const lng = form.longitude ? parseFloat(form.longitude) : defaultLng;

            if (map) {
                map.remove();
            }

            map = L.map(mapContainer.value).setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            const updateCoords = (newLat: number, newLng: number) => {
                form.latitude = parseFloat(String(newLat)).toFixed(8);
                form.longitude = parseFloat(String(newLng)).toFixed(8);
            };

            marker.on('dragend', (e: any) => {
                const position = e.target.getLatLng();
                updateCoords(position.lat, position.lng);
                map.panTo(position);
                reverseGeocode(position.lat, position.lng);
            });

            map.on('click', (e: any) => {
                const position = e.latlng;
                marker.setLatLng(position);
                updateCoords(position.lat, position.lng);
                map.panTo(position);
                reverseGeocode(position.lat, position.lng);
            });

            // Adjust size to fit in dialog layout
            map.invalidateSize();
        }, 200);
    });
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            initMap();
        } else {
            destroyMap();
        }
    }
);

watch(
    () => props.address,
    (newAddress) => {
        if (newAddress) {
            form.label = newAddress.label || '';
            form.address_line1 = newAddress.address_line1 || '';
            form.address_line2 = newAddress.address_line2 || '';
            form.city = newAddress.city || '';
            form.state = newAddress.state || '';
            form.postal_code = newAddress.postal_code || '';
            form.latitude = newAddress.latitude !== undefined && newAddress.latitude !== null ? String(newAddress.latitude) : '';
            form.longitude = newAddress.longitude !== undefined && newAddress.longitude !== null ? String(newAddress.longitude) : '';
        } else {
            form.label = '';
            form.address_line1 = '';
            form.address_line2 = '';
            form.city = '';
            form.state = '';
            form.postal_code = '';
            form.latitude = '';
            form.longitude = '';
        }
        form.clearErrors();
    },
    { immediate: true },
);

const handleOpenUpdate = (value: boolean) => {
    emit('update:open', value);
};

const submit = () => {
    if (props.address) {
        form.put(SavedAddressController.update.url(props.address.id), {
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    } else {
        form.post(SavedAddressController.store.url(), {
            onSuccess: () => {
                emit('update:open', false);
                form.reset();
            },
        });
    }
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{
                    props.address ? 'Edit Address' : 'Add New Address'
                }}</DialogTitle>
                <DialogDescription>
                    Save your delivery address details for quicker checkout
                    later.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4 py-2">
                <div class="grid gap-2">
                    <Label for="label">Address Label (e.g. Home, Work)</Label>
                    <Input
                        id="label"
                        v-model="form.label"
                        placeholder="Home"
                        required
                    />
                    <InputError :message="form.errors.label" />
                </div>

                <div class="grid gap-2">
                    <Label for="address_line1">Street Address</Label>
                    <Input
                        id="address_line1"
                        v-model="form.address_line1"
                        placeholder="123 Main St"
                        required
                    />
                    <InputError :message="form.errors.address_line1" />
                </div>

                <div class="grid gap-2">
                    <Label for="address_line2"
                        >Apartment, Suite, Unit, etc. (Optional)</Label
                    >
                    <Input
                        id="address_line2"
                        v-model="form.address_line2"
                        placeholder="Apt 4B"
                    />
                    <InputError :message="form.errors.address_line2" />
                </div>

                <div class="grid gap-2">
                    <Label for="city">City</Label>
                    <Input
                        id="city"
                        v-model="form.city"
                        placeholder="Accra"
                        required
                    />
                    <InputError :message="form.errors.city" />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="state">State / Region</Label>
                        <Input
                            id="state"
                            v-model="form.state"
                            placeholder="Greater Accra"
                            required
                        />
                        <InputError :message="form.errors.state" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="postal_code">Postal Code</Label>
                        <Input
                            id="postal_code"
                            v-model="form.postal_code"
                            placeholder="00233"
                            required
                        />
                        <InputError :message="form.errors.postal_code" />
                    </div>
                </div>

                <div class="grid gap-2 border-t border-border pt-4">
                    <div class="flex items-center justify-between">
                        <Label class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">Map Location</Label>
                        <button 
                            type="button" 
                            class="text-xs font-bold text-primary hover:underline bg-transparent border-0 cursor-pointer"
                            @click="useCurrentLocation"
                            :disabled="isLocating"
                        >
                            {{ isLocating ? 'Locating...' : 'Use Current Location' }}
                        </button>
                    </div>
                    <div ref="mapContainer" class="h-[150px] w-full rounded-lg border border-border bg-muted/40 relative animate-in fade-in duration-300" style="z-index: 1;"></div>
                    
                    <div class="grid grid-cols-2 gap-2 text-[10px] text-muted-foreground">
                        <div>Lat: {{ form.latitude || 'Not set' }}</div>
                        <div>Lng: {{ form.longitude || 'Not set' }}</div>
                    </div>
                </div>

                <DialogFooter class="pt-4">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Address' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
