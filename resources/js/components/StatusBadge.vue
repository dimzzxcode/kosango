<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

const labels: Record<string, string> = {
    pending_payment: 'Menunggu Pembayaran',
    confirmed: 'Terkonfirmasi',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
    expired: 'Kedaluwarsa',
    unpaid: 'Belum Dibayar',
    partial: 'Dibayar Sebagian',
    paid: 'Lunas',
    pending: 'Menunggu Pembayaran',
    waiting_verification: 'Menunggu Verifikasi',
    rejected: 'Ditolak',
    failed: 'Gagal',
    awaiting_account: 'Menunggu Rekening',
    requested: 'Refund Diajukan',
    draft: 'Draft',
    published: 'Publik',
    available: 'Tersedia',
    maintenance: 'Perbaikan',
    overdue: 'Lewat Jatuh Tempo',
};

const classes: Record<string, string> = {
    pending_payment:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    confirmed:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    completed:
        'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300',
    cancelled:
        'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    expired:
        'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    unpaid: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    partial: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    waiting_verification:
        'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    failed: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    awaiting_account:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    requested:
        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
    draft: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300',
    published:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    available:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    maintenance:
        'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    overdue: 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
};

const label = computed(() => labels[props.status] ?? props.status);
const tone = computed(
    () =>
        classes[props.status] ??
        'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300',
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap"
        :class="tone"
    >
        {{ label }}
    </span>
</template>
