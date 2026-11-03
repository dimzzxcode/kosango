<script setup lang="ts">
import { computed, ref } from 'vue';
import PaymentProofForm from '@/components/PaymentProofForm.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate, formatRupiah, parseDate } from '@/lib/format';
import type { BankAccount, Booking, Invoice } from '@/types';

const props = defineProps<{
    booking: Booking;
    banks: BankAccount[];
}>();

const openInvoiceId = ref<number | null>(null);

const invoices = computed(() => props.booking.invoices ?? []);

const canPay = computed(() =>
    ['pending_payment', 'confirmed'].includes(props.booking.status),
);

const today = new Date();
today.setHours(0, 0, 0, 0);

function isOverdue(invoice: Invoice): boolean {
    return (
        ['unpaid', 'partial'].includes(invoice.status) &&
        parseDate(invoice.due_date).getTime() < today.getTime()
    );
}

function daysLate(invoice: Invoice): number {
    if (!isOverdue(invoice)) {
        return 0;
    }

    return Math.max(
        0,
        Math.round(
            (today.getTime() - parseDate(invoice.due_date).getTime()) /
                86_400_000,
        ),
    );
}

function remaining(invoice: Invoice): number {
    return Math.max(0, invoice.amount + invoice.late_fee - invoice.paid_amount);
}

function toggle(invoiceId: number): void {
    openInvoiceId.value = openInvoiceId.value === invoiceId ? null : invoiceId;
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="invoices.length === 0" class="rounded-xl border border-dashed border-zinc-300 p-5 text-sm text-zinc-500 dark:border-white/15 dark:text-zinc-400">
            Belum ada tagihan untuk booking ini.
        </div>

        <div
            v-for="invoice in invoices"
            :key="invoice.id"
            class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                        :class="
                            invoice.status === 'paid'
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                : 'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-300'
                        "
                    >
                        {{ invoice.payments?.length ? invoice.payments.length : 1 }}
                    </span>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            {{ invoice.number }}
                        </p>
                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                            Jatuh tempo {{ formatDate(invoice.due_date) }}
                            <span class="text-zinc-400">
                                · toleransi sampai
                                {{ formatDate(invoice.grace_until) }}
                            </span>
                        </p>
                        <p
                            v-if="daysLate(invoice) > 0"
                            class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400"
                        >
                            Terlambat {{ daysLate(invoice) }} hari
                            <template v-if="invoice.late_fee > 0">
                                · denda {{ formatRupiah(invoice.late_fee) }}
                                diterapkan
                            </template>
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="font-semibold text-zinc-900 dark:text-white">
                        {{ formatRupiah(invoice.amount + invoice.late_fee) }}
                    </p>
                    <div class="mt-1 flex items-center justify-end gap-1.5">
                        <StatusBadge :status="isOverdue(invoice) ? 'overdue' : invoice.status" />
                    </div>
                    <p
                        v-if="invoice.paid_amount > 0 && invoice.status !== 'paid'"
                        class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
                    >
                        Terbayar {{ formatRupiah(invoice.paid_amount) }}
                    </p>
                </div>
            </div>

            <div v-if="invoice.payments && invoice.payments.length > 0" class="mt-3 space-y-1.5">
                <div
                    v-for="payment in invoice.payments"
                    :key="payment.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-xs dark:bg-white/5"
                >
                    <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                        <StatusBadge :status="payment.status" />
                        {{ formatRupiah(payment.amount) }}
                    </span>
                    <span class="flex items-center gap-3 text-zinc-500 dark:text-zinc-400">
                        <a
                            v-if="payment.proof_url"
                            :href="payment.proof_url"
                            class="font-medium text-emerald-600 underline-offset-4 hover:underline dark:text-emerald-400"
                        >
                            Lihat bukti
                        </a>
                        <span>{{ formatDate(payment.created_at) }}</span>
                    </span>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <button
                    v-if="canPay && ['unpaid', 'partial'].includes(invoice.status)"
                    type="button"
                    class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    @click="toggle(invoice.id)"
                >
                    {{
                        openInvoiceId === invoice.id
                            ? 'Tutup'
                            : remaining(invoice) > 0
                              ? `Bayar ${formatRupiah(remaining(invoice))}`
                              : 'Upload Bukti'
                    }}
                </button>
            </div>

            <PaymentProofForm
                v-if="openInvoiceId === invoice.id"
                :booking-code="booking.code"
                :invoice="invoice"
                :banks="banks"
                @uploaded="openInvoiceId = null"
            />
        </div>
    </div>
</template>
