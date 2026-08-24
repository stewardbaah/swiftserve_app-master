<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    DashboardSquareIcon,
    Store01Icon,
    ShoppingBag01Icon,
    Location01Icon,
    CreditCardIcon,
    Settings01Icon,
    HeartIcon,
} from '@hugeicons/core-free-icons';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import DashboardController, {
    orders,
    payments,
    addresses,
    favorites,
} from '@/actions/App/Http/Controllers/DashboardController';
import { edit as editProfile } from '@/routes/profile';

const dashboardUrl = computed(() => DashboardController.url());

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
        icon: DashboardSquareIcon,
    },
    {
        title: 'Browse Restaurants',
        href: '/restaurants',
        icon: Store01Icon,
    },
    {
        title: 'Favorites',
        href: favorites.url(),
        icon: HeartIcon,
    },
    {
        title: 'Orders',
        href: orders.url(),
        icon: ShoppingBag01Icon,
    },
    {
        title: 'Payments',
        href: payments.url(),
        icon: CreditCardIcon,
    },
    {
        title: 'Saved Addresses',
        href: addresses.url(),
        icon: Location01Icon,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: editProfile(),
        icon: Settings01Icon,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
