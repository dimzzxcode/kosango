<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarCheck,
    CalendarDays,
    Clock3,
    MapPin,
    Ticket,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { gradientForSlug } from '@/lib/accents';
import { formatDate, formatRupiah, periodUnit } from '@/lib/format';
import type { Booking, Invoice } from '@/types';
import { index as bookingsIndex, show } from '@/routes/bookings';
import { index as propertiesIndex } from '@/routes/properties';

const props = defineProps<{
    bookings: Booking[];
    summary: {
        active: number;
        upcoming: number;
        completed: number;
        cancelled: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pesanan Saya',
                href: bookingsIndex(),
            },
        ],
    },
});

const summaryCards = computed(() => [
    {
        label: 'Booking aktif',
        value: props.summary.active,
        icon: CalendarCheck,
        tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    },
    {
        label: 'Akan datang',
        value: props.summary.upcoming,
        icon: CalendarDays,
        tone: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
    },
    {
        label: 'Selesai',
        value: props.summary.completed,
        icon: Clock3,
        tone: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    },
    {
        label: 'Dibatalkan',
        value: props.summary.cancelled,
        icon: Ticket,
        tone: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
    },
]);

function outstanding(booking: Booking): number {
    return (booking.invoices ?? [])
        .filter((invoice: Invoice) => invoice.status === 'unpaid' || invoice.status === 'partial')
        .reduce(
            (total: number, invoice: Invoice) =>
                total + Math.max(0, invoice.amount + invoice.late_fee - invoice.paid_amount),
            0,
        );
}
</script>

<template>
    <Head title="Pesanan Saya" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    Pesanan Saya
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Semua booking, jadwal pembayaran, denda, dan refund ada di
                    sini.
                </p>
            </div>
            <Link
                :href="propertiesIndex()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500"
            >
                Cari penginapan baru
                <ArrowRight class="size-4" />
            </Link>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in summaryCards"
                :key="card.label"
                class="flex items-center gap-3 rounded-xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-xl"
                    :class="card.tone"
                >
                    <component :is="card.icon" class="size-5" />
                </span>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ card.value }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ card.label }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="bookings.length > 0" class="space-y-3">
            <Link
                v-for="booking in bookings"
                :key="booking.id"
                :href="show(booking.code)"
                class="group flex flex-col gap-4 rounded-2xl border border-zinc-200/80 bg-white p-4 transition hover:border-emerald-300 hover:shadow-md sm:flex-row sm:items-center dark:border-white/10 dark:bg-zinc-900"
            >
                <div
                    class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white"
                    :class="gradientForSlug(booking.property.slug)"
                >
                    <Building2 class="size-7" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">
                            {{ booking.property.name }}
                        </h2>
                        <StatusBadge :status="booking.status" />
                        <span
                            v-if="booking.extended_from_id"
                            class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"
                        >
                            Perpanjangan
                        </span>
                    </div>
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="flex items-center gap-1">
                            <MapPin class="size-3.5" />
                            {{ booking.property.city?.name }}
                        </span>
                        <span class="font-mono">{{ booking.code }}</span>
                        <span>{{ booking.room_type.name }}</span>
                    </p>
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                        <CalendarDays class="size-4 text-zinc-400" />
                        {{ formatDate(booking.check_in) }} –
                        {{ formatDate(booking.check_out) }}
                        <span class="text-zinc-400">
                            · {{ booking.duration }}
                            {{ periodUnit(booking.period) }}
                        </span>
                    </p>
                </div>

                <div class="flex items-center justify-between gap-6 sm:flex-col sm:items-end sm:gap-1">
                    <div class="text-right">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ formatRupiah(booking.total_price) }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Sisa {{ formatRupiah(outstanding(booking)) }}
                        </p>
                    </div>
                    <span
                        class="flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        Lihat detail
                        <ArrowRight class="size-3.5 transition group-hover:translate-x-0.5" />
                    </span>
                </div>
            </Link>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center dark:border-white/15 dark:bg-zinc-900"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
            >
                <Ticket class="size-6" />
            </span>
            <h2 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                Belum ada pesanan
            </h2>
            <p class="mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                Mulai cari hotel atau kos di kota favoritmu. Booking pertamamu
                akan muncul di sini.
            </p>
            <Link
                :href="propertiesIndex()"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
            >
                Cari penginapan
                <ArrowRight class="size-4" />
            </Link>
        </div>
    </div>
</template>
