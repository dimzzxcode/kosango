<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    Clock3,
    FileUp,
    History,
    RotateCcw,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate, formatDateTime, formatRupiah, periodLabel, periodUnit, planLabel } from '@/lib/format';
import type { BankAccount, Booking, Invoice, Payment } from '@/types';
import { dashboard } from '@/routes/admin';
import { cancel, complete, index as bookingsIndex } from '@/routes/admin/bookings';
import { reopenUpload } from '@/routes/admin/invoices';
import { verify } from '@/routes/admin/payments';
import { proof as paymentProof } from '@/routes/payments';

const props = defineProps<{
    booking: Booking;
    banks: BankAccount[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Booking', href: bookingsIndex() },
        ],
    },
});

const rejectingId = ref<number | null>(null);
const showCancel = ref(false);

const verifyForm = useForm({
    action: 'approve' as 'approve' | 'reject',
    reason: '',
});

const cancelForm = useForm({
    reason: '',
    bank_name: '',
    account_number: '',
    account_holder: '',
});

const completeForm = useForm({ note: '' });

const invoices = computed(() => props.booking.invoices ?? []);

const waitingPayments = computed<Payment[]>(() =>
    invoices.value.flatMap((invoice: Invoice) =>
        (invoice.payments ?? []).filter((payment) => payment.status === 'waiting_verification'),
    ),
);

const canComplete = computed(
    () => props.booking.status === 'confirmed' && waitingPayments.value.length === 0,
);

const canCancel = computed(() =>
    ['pending_payment', 'confirmed'].includes(props.booking.status),
);

function approve(payment: Payment): void {
    verifyForm.action = 'approve';
    verifyForm.reason = '';
    verifyForm.post(verify(payment.id).url, { preserveScroll: true });
}

function startReject(payment: Payment): void {
    rejectingId.value = payment.id;
    verifyForm.reset();
    verifyForm.action = 'reject';
}

function submitReject(payment: Payment): void {
    verifyForm.action = 'reject';
    verifyForm.post(verify(payment.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            rejectingId.value = null;
            verifyForm.reset();
        },
    });
}

function reopen(invoice: Invoice): void {
    useForm({}).post(reopenUpload(invoice.id).url, { preserveScroll: true });
}

function submitComplete(): void {
    completeForm.post(complete(props.booking.code).url, {
        preserveScroll: true,
        onSuccess: () => completeForm.reset(),
    });
}

function submitCancel(): void {
    cancelForm.post(cancel(props.booking.code).url, {
        preserveScroll: true,
        onSuccess: () => {
            showCancel.value = false;
            cancelForm.reset();
        },
    });
}

function paymentStatusTone(payment: Payment): string {
    return payment.status === 'waiting_verification'
        ? 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'
        : '';
}
</script>

<template>
    <Head :title="`${booking.code} — Panel Admin`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="bookingsIndex()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-emerald-600 dark:text-zinc-400"
            >
                <ArrowLeft class="size-4" />
                Semua booking
            </Link>
            <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">
                {{ booking.code }}
            </span>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    {{ booking.property.name }}
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ booking.user?.name }} · {{ booking.user?.email }} ·
                    {{ booking.user?.phone ?? 'tanpa telepon' }}
                </p>
            </div>
            <StatusBadge :status="booking.status" />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr] xl:items-start">
            <div class="space-y-6">
                <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Detail booking</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Menginap</dt>
                            <dd class="text-right text-zinc-900 dark:text-white">
                                {{ formatDate(booking.check_in) }} – {{ formatDate(booking.check_out) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Durasi</dt>
                            <dd class="text-zinc-900 dark:text-white">
                                {{ booking.duration }} {{ periodUnit(booking.period) }}
                                ({{ periodLabel(booking.period) }})
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Kamar</dt>
                            <dd class="text-zinc-900 dark:text-white">
                                {{ booking.room_type.name }} · {{ booking.room.name }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Tamu</dt>
                            <dd class="text-zinc-900 dark:text-white">{{ booking.guests }} orang</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Cara bayar</dt>
                            <dd class="text-zinc-900 dark:text-white">{{ planLabel(booking.payment_plan) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-zinc-100 pb-2 dark:border-white/5">
                            <dt class="text-zinc-500 dark:text-zinc-400">Total</dt>
                            <dd class="font-semibold text-zinc-900 dark:text-white">
                                {{ formatRupiah(booking.total_price) }}
                            </dd>
                        </div>
                    </dl>
                    <p v-if="booking.note" class="mt-4 rounded-xl bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-white/5 dark:text-zinc-300">
                        {{ booking.note }}
                    </p>
                </section>

                <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Tagihan & pembayaran</h2>

                    <div class="mt-4 space-y-4">
                        <article
                            v-for="invoice in invoices"
                            :key="invoice.id"
                            class="rounded-xl border border-zinc-200/80 p-4 dark:border-white/10"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ invoice.number }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Jatuh tempo {{ formatDate(invoice.due_date) }} · toleransi
                                        {{ formatDate(invoice.grace_until) }}
                                    </p>
                                    <p v-if="invoice.late_fee > 0" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                        Denda {{ formatRupiah(invoice.late_fee) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ formatRupiah(invoice.amount + invoice.late_fee) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Dibayar {{ formatRupiah(invoice.paid_amount) }}
                                    </p>
                                    <StatusBadge :status="invoice.status" />
                                </div>
                            </div>

                            <div v-if="(invoice.payments ?? []).length > 0" class="mt-3 space-y-2">
                                <div
                                    v-for="payment in invoice.payments"
                                    :key="payment.id"
                                    class="rounded-lg bg-zinc-50 p-3 text-xs dark:bg-white/5"
                                    :class="paymentStatusTone(payment)"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="flex items-center gap-2">
                                            <StatusBadge :status="payment.status" />
                                            <span class="font-medium">{{ formatRupiah(payment.amount) }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                {{ payment.bank_account?.bank_name ?? 'manual transfer' }}
                                            </span>
                                        </span>
                                        <span class="flex items-center gap-2">
                                            <a
                                                v-if="payment.proof_url"
                                                :href="paymentProof(payment.id).url"
                                                class="font-medium text-emerald-600 hover:underline dark:text-emerald-400"
                                            >
                                                Lihat bukti
                                            </a>
                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                {{ formatDateTime(payment.created_at) }}
                                            </span>
                                        </span>
                                    </div>

                                    <p v-if="payment.rejection_reason" class="mt-1 text-rose-600 dark:text-rose-400">
                                        Ditolak: {{ payment.rejection_reason }}
                                    </p>

                                    <div v-if="payment.status === 'waiting_verification'" class="mt-2 flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-2.5 py-1.5 font-medium text-white hover:bg-emerald-500"
                                            @click="approve(payment)"
                                        >
                                            <Check class="size-3.5" />
                                            Setujui
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-md border border-rose-200 px-2.5 py-1.5 font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                            @click="startReject(payment)"
                                        >
                                            <X class="size-3.5" />
                                            Tolak
                                        </button>
                                    </div>

                                    <form
                                        v-if="rejectingId === payment.id"
                                        class="mt-2 space-y-2"
                                        @submit.prevent="submitReject(payment)"
                                    >
                                        <input
                                            v-model="verifyForm.reason"
                                            type="text"
                                            placeholder="Alasan penolakan"
                                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-2.5 text-sm dark:border-white/10 dark:bg-zinc-900"
                                        />
                                        <span v-if="verifyForm.errors.reason" class="block text-rose-600">
                                            {{ verifyForm.errors.reason }}
                                        </span>
                                        <button
                                            type="submit"
                                            :disabled="verifyForm.processing"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-rose-600 px-2.5 py-1.5 font-medium text-white hover:bg-rose-500 disabled:opacity-60"
                                        >
                                            Kirim penolakan
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-600 hover:text-emerald-600 dark:text-zinc-300"
                                    @click="reopen(invoice)"
                                >
                                    <FileUp class="size-3.5" />
                                    Buka kembali upload bukti
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <History class="size-5 text-emerald-600" />
                        Riwayat status
                    </h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="history in booking.status_histories ?? []"
                            :key="history.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-white/5"
                        >
                            <span class="text-zinc-600 dark:text-zinc-300">
                                {{ history.from_status ?? 'baru' }} → {{ history.to_status }}
                                <span v-if="history.note" class="block text-xs text-zinc-500">
                                    {{ history.note }}
                                </span>
                            </span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ history.actor?.name ?? 'sistem' }} ·
                                {{ formatDateTime(history.created_at) }}
                            </span>
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-6 xl:sticky xl:top-24">
                <section v-if="booking.refund" class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        <RotateCcw class="size-5 text-sky-600" />
                        Refund
                    </h2>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="font-semibold text-zinc-900 dark:text-white">
                            {{ formatRupiah(booking.refund.amount) }}
                        </span>
                        <StatusBadge :status="booking.refund.status" />
                    </div>
                    <p v-if="booking.refund.bank_name" class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ booking.refund.bank_name }} · {{ booking.refund.account_number }} ·
                        {{ booking.refund.account_holder }}
                    </p>
                    <Link :href="'/admin/refunds'" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:underline">
                        Kelola refund
                    </Link>
                </section>

                <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">Tindakan</h2>

                    <button
                        v-if="canComplete"
                        type="button"
                        :disabled="completeForm.processing"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                        @click="submitComplete"
                    >
                        <CheckCircle2 class="size-4" />
                        Tandai selesai
                    </button>

                    <p v-else-if="booking.status === 'confirmed'" class="mt-4 flex items-start gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <Clock3 class="mt-0.5 size-3.5 shrink-0" />
                        Selesaikan verifikasi pembayaran sebelum menutup booking.
                    </p>

                    <button
                        v-if="canCancel"
                        type="button"
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400 dark:hover:bg-rose-500/10"
                        @click="showCancel = !showCancel"
                    >
                        Batalkan booking
                    </button>

                    <form v-if="showCancel" class="mt-3 space-y-2" @submit.prevent="submitCancel">
                        <textarea
                            v-model="cancelForm.reason"
                            rows="2"
                            placeholder="Alasan pembatalan (wajib)"
                            class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5"
                        />
                        <span v-if="cancelForm.errors.reason" class="block text-xs text-rose-600">
                            {{ cancelForm.errors.reason }}
                        </span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Refund dihitung otomatis oleh server. Isi rekening bila refund lebih dari nol.
                        </p>
                        <input v-model="cancelForm.bank_name" type="text" placeholder="Nama bank" class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                        <input v-model="cancelForm.account_number" type="text" placeholder="Nomor rekening" class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                        <input v-model="cancelForm.account_holder" type="text" placeholder="Nama pemilik rekening" class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                        <span v-for="(message, field) in cancelForm.errors" :key="field" class="block text-xs text-rose-600">
                            {{ message }}
                        </span>
                        <button type="submit" :disabled="cancelForm.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500 disabled:opacity-60">
                            Konfirmasi pembatalan
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</template>
