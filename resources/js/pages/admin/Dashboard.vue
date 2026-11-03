<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarCheck,
    Clock3,
    CreditCard,
    DoorOpen,
    RotateCcw,
    ShieldCheck,
    Ticket,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate, formatRupiah, periodUnit } from '@/lib/format';
import type { Booking } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as bookingsIndex, show as showBooking } from '@/routes/admin/bookings';
import { index as invoicesIndex } from '@/routes/admin/invoices';
import { index as refundsIndex } from '@/routes/admin/refunds';

const props = defineProps<{
    stats: {
        activeBookings: number;
        pendingVerifications: number;
        overdueInvoices: number;
        refundsWaiting: number;
        paidThisMonth: number;
    };
    recentBookings: Booking[];
    pendingPayments: Booking[];
    roomSummary: {
        available: number;
        maintenance: number;
    };
    periods: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Panel Admin',
                href: dashboard(),
            },
        ],
    },
});

const cards = computed(() => [
    {
        label: 'Booking aktif',
        value: String(props.stats.activeBookings),
        icon: CalendarCheck,
        href: bookingsIndex().url,
        tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    },
    {
        label: 'Menunggu verifikasi',
        value: String(props.stats.pendingVerifications),
        icon: ShieldCheck,
        href: invoicesIndex.url({ query: { scope: 'pending' } }),
        tone: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
    },
    {
        label: 'Tagihan terlambat',
        value: String(props.stats.overdueInvoices),
        icon: Clock3,
        href: invoicesIndex.url({ query: { scope: 'overdue' } }),
        tone: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
    },
    {
        label: 'Refund menunggu',
        value: String(props.stats.refundsWaiting),
        icon: RotateCcw,
        href: refundsIndex().url,
        tone: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    },
]);

function pendingPayment(booking: Booking) {
    return booking.invoices?.flatMap((invoice) => invoice.payments ?? []).find(
        (payment) => payment.status === 'waiting_verification',
    );
}
</script>

<template>
    <Head title="Panel Admin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                Panel Admin
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Ringkasan operasional properti yang Anda kelola.
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="card in cards"
                :key="card.label"
                :href="card.href"
                class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200/80 bg-white p-4 transition hover:border-emerald-300 hover:shadow-sm dark:border-white/10 dark:bg-zinc-900"
            >
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                        {{ card.label }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ card.value }}
                    </p>
                </div>
                <span class="flex size-10 items-center justify-center rounded-xl" :class="card.tone">
                    <component :is="card.icon" class="size-5" />
                </span>
            </Link>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
            <span class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <DoorOpen class="size-4 text-emerald-600" />
                Kamar tersedia: <strong>{{ roomSummary.available }}</strong>
            </span>
            <span class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <DoorOpen class="size-4 text-rose-500" />
                Maintenance: <strong>{{ roomSummary.maintenance }}</strong>
            </span>
            <span class="ml-auto flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <CreditCard class="size-4 text-emerald-600" />
                Pembayaran bulan ini: <strong>{{ formatRupiah(stats.paidThisMonth) }}</strong>
            </span>
        </div>

        <div class="grid gap-6 xl:grid-cols-2 xl:items-start">
            <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <ShieldCheck class="size-5 text-emerald-600" />
                        Menunggu verifikasi
                    </h2>
                    <Link :href="invoicesIndex.url({ query: { scope: 'pending' } })" class="text-sm font-medium text-emerald-600 hover:underline">
                        Semua
                    </Link>
                </div>

                <div v-if="pendingPayments.length > 0" class="divide-y divide-zinc-100 dark:divide-white/5">
                    <Link
                        v-for="booking in pendingPayments"
                        :key="booking.id"
                        :href="showBooking(booking.code)"
                        class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-zinc-50 dark:hover:bg-white/5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                {{ booking.property.name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ booking.code }} · {{ booking.user?.name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ formatRupiah(pendingPayment(booking)?.amount ?? 0) }}
                            </p>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                Verifikasi
                            </span>
                        </div>
                    </Link>
                </div>
                <p v-else class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    Tidak ada pembayaran yang menunggu verifikasi.
                </p>
            </section>

            <section class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <Ticket class="size-5 text-emerald-600" />
                        Booking terbaru
                    </h2>
                    <Link :href="bookingsIndex()" class="flex items-center gap-1 text-sm font-medium text-emerald-600 hover:underline">
                        Semua
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <div v-if="recentBookings.length > 0" class="divide-y divide-zinc-100 dark:divide-white/5">
                    <Link
                        v-for="booking in recentBookings"
                        :key="booking.id"
                        :href="showBooking(booking.code)"
                        class="flex flex-wrap items-center gap-3 px-5 py-3.5 transition hover:bg-zinc-50 dark:hover:bg-white/5"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                {{ booking.property.name }}
                            </p>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ booking.code }} · {{ formatDate(booking.check_in) }} –
                                {{ formatDate(booking.check_out) }} ·
                                {{ booking.duration }} {{ periodUnit(booking.period) }}
                            </p>
                        </div>
                        <StatusBadge :status="booking.status" />
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ formatRupiah(booking.total_price) }}
                        </span>
                    </Link>
                </div>
                <p v-else class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    Belum ada booking.
                </p>
            </section>
        </div>
    </div>
</template>
