<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Search, Ticket } from '@lucide/vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate, formatRupiah, periodUnit } from '@/lib/format';
import type { Booking, Paginated } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as bookingsIndex, show } from '@/routes/admin/bookings';

const props = defineProps<{
    bookings: Paginated<Booking>;
    filters: {
        status?: string;
        q?: string;
        property?: string;
    };
    statuses: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Booking', href: bookingsIndex() },
        ],
    },
});
</script>

<template>
    <Head title="Booking — Panel Admin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                Booking
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Semua booking pada properti yang Anda kelola.
            </p>
        </div>

        <form
            :action="bookingsIndex().url"
            method="GET"
            class="grid gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 sm:grid-cols-[1.4fr_1fr_auto] dark:border-white/10 dark:bg-zinc-900"
        >
            <label class="relative block">
                <span class="sr-only">Cari kode atau tamu</span>
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                <input
                    type="search"
                    name="q"
                    :value="filters.q"
                    placeholder="Kode booking, nama, atau email tamu"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 pr-3 pl-9 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                />
            </label>

            <label class="block">
                <span class="sr-only">Status</span>
                <select
                    name="status"
                    :value="filters.status"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                >
                    <option value="">Semua status</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </label>

            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500"
            >
                <Search class="size-4" />
                Filter
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
            <div v-if="bookings.data.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200/80 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Booking</th>
                            <th class="px-4 py-3 font-medium">Tamu</th>
                            <th class="px-4 py-3 font-medium">Menginap</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                        <tr
                            v-for="booking in bookings.data"
                            :key="booking.id"
                            class="transition hover:bg-zinc-50 dark:hover:bg-white/5"
                        >
                            <td class="px-4 py-3">
                                <Link :href="show(booking.code)" class="font-mono text-xs font-medium text-emerald-600 hover:underline">
                                    {{ booking.code }}
                                </Link>
                                <p class="mt-0.5 text-zinc-900 dark:text-white">
                                    {{ booking.property.name }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ booking.room_type.name }} · {{ booking.room.name }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-zinc-900 dark:text-white">{{ booking.user?.name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ booking.user?.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ formatDate(booking.check_in) }} –
                                {{ formatDate(booking.check_out) }}
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ booking.duration }} {{ periodUnit(booking.period) }}
                                </p>
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                                {{ formatRupiah(booking.total_price) }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="booking.status" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="flex flex-col items-center px-6 py-16 text-center">
                <Ticket class="size-8 text-zinc-400" />
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    Belum ada booking yang cocok dengan filter.
                </p>
            </div>
        </div>

        <PaginationLinks :links="bookings.links" />
    </div>
</template>
