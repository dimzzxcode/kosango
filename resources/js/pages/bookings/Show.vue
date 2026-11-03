<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Building2,
    CalendarDays,
    CheckCircle2,
    CreditCard,
    Landmark,
    MapPin,
    ReceiptText,
    RotateCcw,
    ShieldCheck,
    Star,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PaymentSchedule from '@/components/PaymentSchedule.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { gradientForSlug } from '@/lib/accents';
import {
    daysBetween,
    formatDate,
    formatDateLong,
    formatRupiah,
    periodLabel,
    periodUnit,
    planLabel,
    todayIso,
} from '@/lib/format';
import type { BankAccount, Booking, Invoice, Page, Refund } from '@/types';
import {
    cancel,
    extend,
    index as bookingsIndex,
    refundPreview,
} from '@/routes/bookings';
import { show as showProperty } from '@/routes/properties';
import { account as refundAccount } from '@/routes/refunds';
import { store as storeReview } from '@/routes/reviews';

type RefundPreview = {
    eligible: boolean;
    amount: number;
    reason: string | null;
};

const props = defineProps<{
    booking: Booking;
    banks: BankAccount[];
    pages: Page[];
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

const showCancelForm = ref(false);
const showExtendForm = ref(false);
const previewLoading = ref(false);
const preview = ref<RefundPreview | null>(null);

const page = usePage();
const rules = computed(() => page.props.bookingRules);

const cancelForm = useForm({
    reason: '',
    bank_name: '',
    account_number: '',
    account_holder: '',
});

const extendForm = useForm({
    period: props.booking.period,
    duration: 1,
    payment_plan: 'full',
    guests: props.booking.guests,
    note: '',
});

const reviewForm = useForm({
    rating: 5,
    comment: '',
});

const refundForm = useForm({
    bank_name: '',
    account_number: '',
    account_holder: '',
});

const invoices = computed(() => props.booking.invoices ?? []);
const refund = computed<Refund | null>(() => props.booking.refund ?? null);

const paidAmount = computed(() => props.booking.total_paid);
const remainingAmount = computed(() =>
    invoices.value
        .filter((invoice: Invoice) => invoice.status === 'unpaid' || invoice.status === 'partial')
        .reduce(
            (total: number, invoice: Invoice) =>
                total + Math.max(0, invoice.amount + invoice.late_fee - invoice.paid_amount),
            0,
        ),
);

const paymentProgress = computed(() => {
    if (props.booking.total_price <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((paidAmount.value / props.booking.total_price) * 100),
    );
});

const isCancellable = computed(
    () =>
        ['pending_payment', 'confirmed'].includes(props.booking.status) &&
        !(refund.value && ['awaiting_account', 'requested'].includes(refund.value.status)),
);

const daysToCheckOut = computed(() =>
    daysBetween(todayIso(), props.booking.check_out),
);

const canExtend = computed(
    () =>
        props.booking.status === 'confirmed' &&
        daysToCheckOut.value >= 0 &&
        daysToCheckOut.value <= rules.value.extensionOpenDaysBeforeEnd,
);

const canReview = computed(
    () => props.booking.status === 'completed' && !props.booking.review,
);

const canSubmitRefundAccount = computed(
    () =>
        refund.value !== null &&
        ['awaiting_account', 'rejected'].includes(refund.value.status),
);

const roomRates = computed(() =>
    (props.booking.room_type?.prices ?? []).filter((price) => price.is_active),
);

const availablePeriods = computed(() => {
    const periods = roomRates.value.map((rate) => rate.period);

    return periods.length > 0 ? periods : [props.booking.period];
});

const selectedRate = computed(
    () => roomRates.value.find((rate) => rate.period === extendForm.period) ?? null,
);

const extendPlans = computed(() =>
    extendForm.period === 'yearly'
        ? [
              { value: 'full', label: 'Lunas sekaligus' },
              { value: 'installment', label: 'Cicilan' },
          ]
        : [
              { value: 'full', label: 'Lunas sekaligus' },
              { value: 'per_period', label: 'Bayar per periode' },
          ],
);

function openCancelForm(): void {
    showCancelForm.value = true;
    void loadRefundPreview();
}

function toggleCancelForm(): void {
    if (showCancelForm.value) {
        showCancelForm.value = false;
        preview.value = null;

        return;
    }

    openCancelForm();
}

async function loadRefundPreview(): Promise<void> {
    preview.value = null;
    previewLoading.value = true;

    try {
        const response = await fetch(refundPreview(props.booking.code).url, {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            preview.value = (await response.json()) as RefundPreview;
        }
    } finally {
        previewLoading.value = false;
    }
}

function submitCancellation(): void {
    cancelForm.post(cancel(props.booking.code).url, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelForm.value = false;
            cancelForm.reset();
        },
    });
}

function submitExtension(): void {
    extendForm.post(extend(props.booking.code).url, {
        preserveScroll: true,
        onSuccess: () => {
            showExtendForm.value = false;
            extendForm.reset();
        },
    });
}

function submitReview(): void {
    reviewForm.post(storeReview(props.booking.code).url, {
        preserveScroll: true,
    });
}

function submitRefundAccount(): void {
    if (!refund.value) {
        return;
    }

    refundForm.post(refundAccount(refund.value.id).url, {
        preserveScroll: true,
        onSuccess: () => refundForm.reset(),
    });
}

function onPeriodChange(): void {
    const plans = extendPlans.value.map((plan) => plan.value);

    if (!plans.includes(extendForm.payment_plan)) {
        extendForm.payment_plan = 'full';
    }

    if (selectedRate.value) {
        extendForm.duration = Math.min(
            Math.max(extendForm.duration, selectedRate.value.min_duration),
            selectedRate.value.max_duration,
        );
    }
}

const statusMessage = computed(() => {
    if (props.booking.status === 'pending_payment' && props.booking.expires_at) {
        return `Selesaikan pembayaran pertama sebelum ${formatDateLong(props.booking.expires_at)}.`;
    }

    if (props.booking.status === 'confirmed') {
        return 'Booking terkonfirmasi. Semua tagihan harus lunas sebelum check-out.';
    }

    if (props.booking.status === 'completed') {
        return 'Masa sewa selesai. Terima kasih sudah menginap di KosanGo.';
    }

    return null;
});
</script>

<template>
    <Head :title="`Pesanan ${booking.code}`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="bookingsIndex()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-emerald-600 dark:text-zinc-400"
            >
                <ArrowLeft class="size-4" />
                Semua pesanan
            </Link>
            <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">
                {{ booking.code }}
            </span>
        </div>

        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br p-6 text-white sm:p-8"
            :class="gradientForSlug(booking.property.slug)"
        >
            <div
                class="absolute -top-20 -right-12 size-56 rounded-full border border-white/20 bg-white/10"
            />
            <div class="relative">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full border border-white/25 bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        {{ booking.property.type === 'kos' ? 'Kos' : 'Hotel' }}
                    </span>
                    <span
                        class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        {{ statusMessage ? 'Perlu perhatian' : 'Booking' }}
                    </span>
                </div>
                <h1 class="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ booking.property.name }}
                </h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white/80">
                    <span class="flex items-center gap-1">
                        <MapPin class="size-4" />
                        {{ booking.property.city?.name }}
                    </span>
                    <span>{{ booking.room_type.name }}</span>
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <StatusBadge :status="booking.status" />
                    <Link
                        :href="showProperty(booking.property.slug)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-white/25 bg-white/10 px-3 py-1.5 text-xs font-medium backdrop-blur-sm transition hover:bg-white/20"
                    >
                        <Building2 class="size-3.5" />
                        Lihat penginapan
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-if="statusMessage"
            class="flex items-start gap-3 rounded-2xl border p-4 text-sm"
            :class="
                booking.status === 'pending_payment'
                    ? 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200'
            "
        >
            <AlertTriangle class="mt-0.5 size-4 shrink-0" />
            <p>{{ statusMessage }}</p>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_1fr] xl:items-start">
            <div class="space-y-6">
                <section
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <CalendarDays class="size-5 text-emerald-600" />
                        Detail menginap
                    </h2>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-white/5">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Check-in
                            </p>
                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ formatDateLong(booking.check_in) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-zinc-50 p-3 dark:bg-white/5">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Check-out
                            </p>
                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ formatDateLong(booking.check_out) }}
                            </p>
                        </div>
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Tipe kamar</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ booking.room_type.name }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Kamar</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ booking.room.name }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Tamu</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ booking.guests }} orang
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Durasi</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ booking.duration }}
                                {{ periodUnit(booking.period) }}
                                · {{ periodLabel(booking.period) }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Cara bayar</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ planLabel(booking.payment_plan) }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Tarif satuan</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">
                                {{ formatRupiah(booking.unit_price) }} /
                                {{ periodUnit(booking.period) }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Terbayar {{ formatRupiah(paidAmount) }}
                            </span>
                            <span class="font-semibold text-zinc-900 dark:text-white">
                                {{ formatRupiah(booking.total_price) }}
                            </span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10">
                            <div
                                class="h-full rounded-full bg-emerald-500"
                                :style="{ width: `${paymentProgress}%` }"
                            />
                        </div>
                        <p
                            v-if="remainingAmount > 0"
                            class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400"
                        >
                            Sisa tagihan {{ formatRupiah(remainingAmount) }}
                        </p>
                    </div>

                    <p
                        v-if="booking.note"
                        class="mt-4 rounded-xl bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-white/5 dark:text-zinc-300"
                    >
                        {{ booking.note }}
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                            <ReceiptText class="size-5 text-emerald-600" />
                            Jadwal pembayaran
                        </h2>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            Jatuh tempo & denda dihitung server
                        </span>
                    </div>

                    <div class="mt-4">
                        <PaymentSchedule :booking="booking" :banks="banks" />
                    </div>
                </section>

                <section
                    v-if="refund"
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                                <RotateCcw class="size-5 text-sky-600" />
                                Refund
                            </h2>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Diajukan oleh
                                {{ refund.initiated_by === 'customer' ? 'Anda' : 'admin properti' }}.
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-zinc-900 dark:text-white">
                                {{ formatRupiah(refund.amount) }}
                            </p>
                            <StatusBadge :status="refund.status" />
                        </div>
                    </div>

                    <div
                        v-if="refund.rejection_reason"
                        class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                    >
                        {{ refund.rejection_reason }}
                    </div>

                    <form
                        v-if="canSubmitRefundAccount"
                        class="mt-4 space-y-3 rounded-xl bg-zinc-50 p-4 dark:bg-white/5"
                        @submit.prevent="submitRefundAccount"
                    >
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            Rekening tujuan refund
                        </p>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Bank</span>
                                <input
                                    v-model="refundForm.bank_name"
                                    type="text"
                                    placeholder="BCA"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-zinc-900"
                                />
                                <span class="mt-1 block text-xs text-rose-600" v-if="refundForm.errors.bank_name">
                                    {{ refundForm.errors.bank_name }}
                                </span>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nomor rekening</span>
                                <input
                                    v-model="refundForm.account_number"
                                    type="text"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-zinc-900"
                                />
                                <span class="mt-1 block text-xs text-rose-600" v-if="refundForm.errors.account_number">
                                    {{ refundForm.errors.account_number }}
                                </span>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama pemilik</span>
                                <input
                                    v-model="refundForm.account_holder"
                                    type="text"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-zinc-900"
                                />
                                <span class="mt-1 block text-xs text-rose-600" v-if="refundForm.errors.account_holder">
                                    {{ refundForm.errors.account_holder }}
                                </span>
                            </label>
                        </div>
                        <button
                            type="submit"
                            :disabled="refundForm.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 disabled:opacity-60"
                        >
                            <Landmark class="size-4" />
                            Simpan rekening
                        </button>
                    </form>

                    <div v-else-if="refund.bank_name" class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ refund.bank_name }} · {{ refund.account_number }} ·
                        {{ refund.account_holder }}
                    </div>

                    <a
                        v-if="refund.proof_url"
                        :href="refund.proof_url"
                        class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400"
                    >
                        Lihat bukti transfer refund
                    </a>
                </section>

                <section
                    v-if="canReview || booking.review"
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <Star class="size-5 text-amber-500" />
                        Ulasan
                    </h2>

                    <div v-if="booking.review" class="mt-3">
                        <div class="flex items-center gap-0.5">
                            <Star
                                v-for="index in 5"
                                :key="index"
                                class="size-4"
                                :class="
                                    index <= booking.review.rating
                                        ? 'fill-amber-400 text-amber-400'
                                        : 'text-zinc-300 dark:text-zinc-600'
                                "
                            />
                        </div>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ booking.review.comment }}
                        </p>
                        <p
                            v-if="!booking.review.is_visible"
                            class="mt-2 text-xs text-amber-600 dark:text-amber-400"
                        >
                            Ulasan sedang ditinjau moderator.
                        </p>
                    </div>

                    <form v-else class="mt-3 space-y-3" @submit.prevent="submitReview">
                        <div>
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                Rating
                            </span>
                            <div class="flex items-center gap-1">
                                <button
                                    v-for="value in 5"
                                    :key="value"
                                    type="button"
                                    class="rounded p-0.5"
                                    @click="reviewForm.rating = value"
                                >
                                    <Star
                                        class="size-6 transition"
                                        :class="
                                            value <= reviewForm.rating
                                                ? 'fill-amber-400 text-amber-400'
                                                : 'text-zinc-300 hover:text-amber-300 dark:text-zinc-600'
                                        "
                                    />
                                </button>
                            </div>
                        </div>
                        <label class="block">
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                Komentar (opsional)
                            </span>
                            <textarea
                                v-model="reviewForm.comment"
                                rows="3"
                                class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                                placeholder="Ceritakan pengalaman menginapmu"
                            />
                        </label>
                        <button
                            type="submit"
                            :disabled="reviewForm.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 disabled:opacity-60"
                        >
                            <CheckCircle2 class="size-4" />
                            Kirim ulasan
                        </button>
                    </form>
                </section>
            </div>

            <aside class="space-y-6 xl:sticky xl:top-24">
                <section
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <Wallet class="size-5 text-emerald-600" />
                        Tindakan
                    </h2>

                    <div class="mt-4 space-y-2">
                        <button
                            v-if="canExtend"
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-500"
                            @click="showExtendForm = !showExtendForm"
                        >
                            <CalendarDays class="size-4" />
                            {{ showExtendForm ? 'Tutup form perpanjangan' : 'Perpanjang sewa' }}
                        </button>

                        <button
                            v-if="isCancellable"
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400 dark:hover:bg-rose-500/10"
                            @click="toggleCancelForm"
                        >
                            <AlertTriangle class="size-4" />
                            {{ showCancelForm ? 'Tutup' : 'Batalkan booking' }}
                        </button>

                        <p
                            v-if="!canExtend && !isCancellable"
                            class="rounded-xl bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-white/5 dark:text-zinc-400"
                        >
                            Tidak ada tindakan yang tersedia untuk booking ini.
                        </p>
                    </div>

                    <form
                        v-if="showExtendForm"
                        class="mt-4 space-y-3 rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/5"
                        @submit.prevent="submitExtension"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Periode</span>
                                <select
                                    v-model="extendForm.period"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                    @change="onPeriodChange"
                                >
                                    <option
                                        v-for="period in availablePeriods"
                                        :key="period"
                                        :value="period"
                                    >
                                        {{ periodLabel(period) }}
                                    </option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                    Durasi ({{ periodUnit(extendForm.period) }})
                                </span>
                                <input
                                    v-model.number="extendForm.duration"
                                    type="number"
                                    :min="selectedRate?.min_duration ?? 1"
                                    :max="selectedRate?.max_duration ?? 120"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                />
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Tamu</span>
                                <input
                                    v-model.number="extendForm.guests"
                                    type="number"
                                    min="1"
                                    :max="booking.room_type.capacity"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                />
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Cara bayar</span>
                                <select
                                    v-model="extendForm.payment_plan"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                >
                                    <option
                                        v-for="plan in extendPlans"
                                        :key="plan.value"
                                        :value="plan.value"
                                    >
                                        {{ plan.label }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <label class="block">
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                Catatan (opsional)
                            </span>
                            <textarea
                                v-model="extendForm.note"
                                rows="2"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-zinc-900"
                            />
                        </label>

                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Perpanjangan dimulai {{ formatDate(booking.check_out) }}.
                            Tagihan baru akan dibuat otomatis.
                        </p>

                        <p
                            v-for="(message, field) in extendForm.errors"
                            :key="field"
                            class="text-xs text-rose-600"
                        >
                            {{ message }}
                        </p>

                        <button
                            type="submit"
                            :disabled="extendForm.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                        >
                            Buat perpanjangan
                        </button>
                    </form>

                    <form
                        v-if="showCancelForm"
                        class="mt-4 space-y-3 rounded-xl border border-rose-100 bg-rose-50/50 p-4 dark:border-rose-500/20 dark:bg-rose-500/5"
                        @submit.prevent="submitCancellation"
                    >
                        <div v-if="previewLoading" class="text-xs text-zinc-500">
                            Menghitung refund...
                        </div>
                        <div
                            v-else-if="preview"
                            class="rounded-lg bg-white px-3 py-2 text-xs dark:bg-zinc-900"
                        >
                            <p class="font-medium text-zinc-900 dark:text-white">
                                Refund diperkirakan {{ formatRupiah(preview.amount) }}
                            </p>
                            <p class="mt-1 text-zinc-500 dark:text-zinc-400">
                                {{ preview.reason }}
                            </p>
                        </div>

                        <label class="block">
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Alasan</span>
                            <textarea
                                v-model="cancelForm.reason"
                                rows="2"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-zinc-900"
                            />
                        </label>

                        <template v-if="preview && preview.amount > 0">
                            <p class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                Rekening tujuan refund
                            </p>
                            <div class="grid gap-2">
                                <input
                                    v-model="cancelForm.bank_name"
                                    type="text"
                                    placeholder="Nama bank"
                                    class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                />
                                <input
                                    v-model="cancelForm.account_number"
                                    type="text"
                                    placeholder="Nomor rekening"
                                    class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                />
                                <input
                                    v-model="cancelForm.account_holder"
                                    type="text"
                                    placeholder="Nama pemilik rekening"
                                    class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                />
                            </div>
                        </template>

                        <p
                            v-for="(message, field) in cancelForm.errors"
                            :key="field"
                            class="text-xs text-rose-600"
                        >
                            {{ message }}
                        </p>

                        <button
                            type="submit"
                            :disabled="cancelForm.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-60"
                        >
                            Konfirmasi pembatalan
                        </button>
                    </form>
                </section>

                <section
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                        <ShieldCheck class="size-4 text-emerald-600" />
                        Kebijakan singkat
                    </h2>
                    <ul class="mt-3 space-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <li class="flex gap-2">
                            <CreditCard class="mt-0.5 size-3.5 shrink-0" />
                            Denda keterlambatan dikenakan sekali setelah masa toleransi.
                        </li>
                        <li class="flex gap-2">
                            <RotateCcw class="mt-0.5 size-3.5 shrink-0" />
                            Refund penuh pokok sewa bila dibatalkan dalam masa tenggang dan belum check-in.
                        </li>
                        <li class="flex gap-2">
                            <Users class="mt-0.5 size-3.5 shrink-0" />
                            Perpanjangan tersedia menjelang akhir masa sewa.
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>
