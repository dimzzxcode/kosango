<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    FileText,
    Landmark,
    LayoutGrid,
    MapPin,
    RotateCcw,
    Search,
    Sparkles,
    Star,
    Ticket,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { dashboard as customerDashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminBookings } from '@/routes/admin/bookings';
import { index as adminInvoices } from '@/routes/admin/invoices';
import { index as adminRefunds } from '@/routes/admin/refunds';
import { index as bookingsIndex } from '@/routes/bookings';
import { index as propertiesIndex } from '@/routes/properties';
import { index as bankAccountsIndex } from '@/routes/superadmin/bank-accounts';
import { index as citiesIndex } from '@/routes/superadmin/cities';
import { index as facilitiesIndex } from '@/routes/superadmin/facilities';
import { index as pagesIndex } from '@/routes/superadmin/pages';
import { index as superadminPropertiesIndex } from '@/routes/superadmin/properties';
import { index as reviewsIndex } from '@/routes/superadmin/reviews';
import { index as usersIndex } from '@/routes/superadmin/users';
import type { NavItem } from '@/types';

const page = usePage();
const role = computed(() => page.props.auth.user.role);
const homeUrl = computed(() =>
    role.value === 'customer' ? customerDashboard() : adminDashboard(),
);

const customerNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: customerDashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Cari Penginapan',
        href: propertiesIndex(),
        icon: Search,
    },
    {
        title: 'Pesanan Saya',
        href: bookingsIndex(),
        icon: Ticket,
    },
];

const staffNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: adminDashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Booking',
        href: adminBookings(),
        icon: Ticket,
    },
    {
        title: 'Invoice',
        href: adminInvoices(),
        icon: CreditCard,
    },
    {
        title: 'Refund',
        href: adminRefunds(),
        icon: RotateCcw,
    },
];

const masterNavItems: NavItem[] = [
    {
        title: 'Properti',
        href: superadminPropertiesIndex(),
        icon: Building2,
    },
    {
        title: 'Kota',
        href: citiesIndex(),
        icon: MapPin,
    },
    {
        title: 'Fasilitas',
        href: facilitiesIndex(),
        icon: Sparkles,
    },
    {
        title: 'Rekening Bank',
        href: bankAccountsIndex(),
        icon: Landmark,
    },
    {
        title: 'Halaman',
        href: pagesIndex(),
        icon: FileText,
    },
    {
        title: 'Pengguna',
        href: usersIndex(),
        icon: Users,
    },
    {
        title: 'Ulasan',
        href: reviewsIndex(),
        icon: Star,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="role === 'customer'" :items="customerNavItems" />
            <template v-else>
                <NavMain :items="staffNavItems" label="Operasional" />
                <NavMain
                    v-if="role === 'superadmin'"
                    :items="masterNavItems"
                    label="Master Data"
                />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
