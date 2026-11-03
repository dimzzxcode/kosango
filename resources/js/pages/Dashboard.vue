<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    CalendarCheck,
    CalendarDays,
    CreditCard,
    MapPin,
    RotateCcw,
    Sparkles,
    Ticket,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { gradientForSlug } from '@/lib/accents';
import {
    formatDate,
    formatDateLong,
    formatRupiah,
    parseDate,
    periodUnit,
} from '@/lib/format';
import type { Booking, Invoice, Refund } from '@/types';
import { dashboard } from '@/routes';
import { index as bookingsIndex, show as showBooking } from '@/routes/bookings';
import { index as propertiesIndex } from '@/routes/properties';

type InvoiceWithBooking = Invoice & {
    booking: Booking;
};

type RefundWithBooking = Refund & {
    booking: Booking;
};

type AppNotification = {
    id: string;
    data: {
        title?: string;
        message?: string;
        url?: string | null;
    };
    created_at: string;
    read_at: string | null;
};

const props = defineProps<{
    upcomingBooking: Booking | null;
    recentBookings: Booking[];
    pendingInvoices: InvoiceWithBooking[];
    refunds: RefundWithBooking[];
    notifications: AppNotification[];
    stats: {
        activeBookings: number;
        totalPaid: number;
        outstanding: number;
        lateFees: number;
        totalBookings: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    if (hour < 19) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
});

const today = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);

const statCards = computed(() => [
    {
        label: 'Booking aktif',
        value: String(props.stats.activeBookings),
        hint: `${props.stats.totalBookings} total pesanan`,
        icon: CalendarCheck,
        tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
    },
    {
        label: 'Total dibayar',
        value: formatRupiah(props.stats.totalPaid),
        hint: 'Akumulasi seluruh pesanan',
        icon: Wallet,
        tone: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
    },
    {
        label: 'Tagihan menunggu',
        value: formatRupiah(props.stats.outstanding),
        hint: `${props.pendingInvoices.length} tagihan terdekat`,
        icon: CreditCard,
        tone: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
    },
    {
        label: 'Denda aktif',
        value: formatRupiah(props.stats.lateFees),
        hint: 'Dihitung otomatis dari keterlambatan',
        icon: AlertTriangle,
        tone: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
    },
]);

const upcomingProgress = computed(() => {
    if (!props.upcomingBooking || props.upcomingBooking.total_price <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (props.upcomingBooking.total_paid /
                props.upcomingBooking.total_price) *
                100,
        ),
    );
});

function daysUntil(date: string): number {
    const target = parseDate(date).getTime();
    const now = new Date();
    now.setHours(0, 0, 0, 0);

    return Math.max(0, Math.round((target - now.getTime()) / 86_400_000));
}

function isOverdue(invoice: Invoice): boolean {
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);

    return (
        ['unpaid', 'partial'].includes(invoice.status) &&
        parseDate(invoice.due_date).getTime() < todayDate.getTime()
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    {{ today }}
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    {{ greeting }}, {{ user?.name ?? 'Tamu' }}
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Ini ringkasan perjalanan dan pembayaranmu di KosanGo.
                </p>
            </div>
            <Link
                :href="propertiesIndex()"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500"
            >
                <Sparkles class="size-4" />
                Cari penginapan
            </Link>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="rounded-xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                            {{ card.label }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                            {{ card.value }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                            {{ card.hint }}
                        </p>
                    </div>
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                        :class="card.tone"
                    >
                        <component :is="card.icon" class="size-5" />
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_1fr] xl:items-start">
            <section
                class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <CalendarDays class="size-5 text-emerald-600" />
                        Perjalanan berikutnya
                    </h2>
                    <Link
                        v-if="upcomingBooking"
                        :href="showBooking(upcomingBooking.code)"
                        class="text-sm font-medium text-emerald-600 hover:underline"
                    >
                        Detail
                    </Link>
                </div>

                <div v-if="upcomingBooking" class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <StatusBadge :status="upcomingBooking.status" />
                            <h3 class="mt-3 text-xl font-semibold text-zinc-900 dark:text-white">
                                {{ upcomingBooking.property.name }}
                            </h3>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <MapPin class="size-4" />
                                {{ upcomingBooking.property.city?.name }} ·
                                {{ upcomingBooking.room_type.name }}
                            </p>
                        </div>
                        <span
                            class="rounded-xl bg-gradient-to-br px-4 py-3 text-white"
                            :class="gradientForSlug(upcomingBooking.property.slug)"
                        >
                            <span class="block text-2xl font-semibold">
                                {{ daysUntil(upcomingBooking.check_in) }}
                            </span>
                            <span class="text-xs text-white/80">hari lagi</span>
                        </span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-white/5">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Check-in
                            </p>
                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ formatDateLong(upcomingBooking.check_in) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-white/5">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Check-out
                            </p>
                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ formatDateLong(upcomingBooking.check_out) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <span class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                            <Users class="size-4" />
                            {{ upcomingBooking.guests }} tamu ·
                            {{ upcomingBooking.duration }}
                            {{ periodUnit(upcomingBooking.period) }}
                        </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">
                            {{ formatRupiah(upcomingBooking.total_price) }}
                        </span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                        <div
                            class="h-full rounded-full bg-emerald-500"
                            :style="{ width: `${upcomingProgress}%` }"
                        />
                    </div>
                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                        Dibayar {{ formatRupiah(upcomingBooking.total_paid) }} dari
                        {{ formatRupiah(upcomingBooking.total_price) }}
                    </p>
                </div>

                <div v-else class="flex flex-col items-center px-6 py-12 text-center">
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
                    >
                        <Building2 class="size-6" />
                    </span>
                    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">
                        Belum ada perjalanan mendatang
                    </h3>
                    <p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                        Cari hotel atau kos di kota favoritmu dan mulai rencanakan
                        perjalanan berikutnya.
                    </p>
                    <Link
                        :href="propertiesIndex()"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                    >
                        Cari penginapan
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </section>

            <section
                class="rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <CreditCard class="size-5 text-emerald-600" />
                        Tagihan berikutnya
                    </h2>
                    <Link
                        :href="bookingsIndex()"
                        class="text-sm font-medium text-zinc-500 hover:text-emerald-600 dark:text-zinc-400"
                    >
                        Semua
                    </Link>
                </div>

                <div v-if="pendingInvoices.length > 0" class="divide-y divide-zinc-100 dark:divide-white/5">
                    <Link
                        v-for="invoice in pendingInvoices"
                        :key="invoice.id"
                        :href="showBooking(invoice.booking.code)"
                        class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-zinc-50 dark:hover:bg-white/5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                                {{ invoice.number }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ invoice.booking.property.name }} ·
                                {{ formatDate(invoice.due_date) }}
                            </p>
                            <p
                                v-if="isOverdue(invoice)"
                                class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"
                            >
                                Melewati jatuh tempo
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ formatRupiah(invoice.remaining) }}
                            </p>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                Bayar
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-else class="px-5 py-10 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Tidak ada tagihan menunggu. Semua pembayaran beres.
                    </p>
                </div>

                <div
                    v-if="refunds.length > 0"
                    class="border-t border-zinc-200/80 px-5 py-4 dark:border-white/10"
                >
                    <p class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-white">
                        <RotateCcw class="size-4 text-sky-600" />
                        Refund terbaru
                    </p>
                    <div class="mt-2 space-y-2">
                        <Link
                            v-for="refund in refunds"
                            :key="refund.id"
                            :href="showBooking(refund.booking.code)"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="truncate text-zinc-600 dark:text-zinc-300">
                                {{ refund.booking.property.name }}
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    {{ formatRupiah(refund.amount) }}
                                </span>
                                <StatusBadge :status="refund.status" />
                            </span>
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <section
            class="rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900"
        >
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                    <Ticket class="size-5 text-emerald-600" />
                    Pesanan terbaru
                </h2>
                <Link
                    :href="bookingsIndex()"
                    v-if="recentBookings.length > 0"
                    class="text-sm font-medium text-emerald-600 hover:underline"
                >
                    Lihat semua
                </Link>
            </div>

            <div v-if="recentBookings.length > 0" class="divide-y divide-zinc-100 dark:divide-white/5">
                <Link
                    v-for="booking in recentBookings"
                    :key="booking.id"
                    :href="showBooking(booking.code)"
                    class="flex flex-wrap items-center gap-4 px-5 py-4 transition hover:bg-zinc-50 dark:hover:bg-white/5"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white"
                        :class="gradientForSlug(booking.property.slug)"
                    >
                        <Building2 class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">
                            {{ booking.property.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ formatDate(booking.check_in) }} –
                            {{ formatDate(booking.check_out) }} ·
                            {{ booking.room_type.name }}
                        </p>
                    </div>
                    <StatusBadge :status="booking.status" />
                    <span class="w-24 text-right text-sm font-semibold text-zinc-900 dark:text-white">
                        {{ formatRupiah(booking.total_price) }}
                    </span>
                </Link>
            </div>

            <div v-else class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Belum ada pesanan.
            </div>
        </section>
    </div>
</template>
