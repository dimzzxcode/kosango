<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FileUp, Search } from '@lucide/vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate, formatRupiah } from '@/lib/format';
import type { Invoice, Paginated } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as invoicesIndex, reopenUpload } from '@/routes/admin/invoices';
import { show as showBooking } from '@/routes/admin/bookings';

type InvoiceRow = Invoice & {
    booking: {
        code: string;
        property: { name: string };
        room_type: { name: string };
        user: { name: string };
    };
};

const props = defineProps<{
    invoices: Paginated<InvoiceRow>;
    filters: {
        scope?: string;
        status?: string;
        q?: string;
    };
    statuses: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Tagihan', href: invoicesIndex() },
        ],
    },
});

const reopenForm = useForm({});

function reopen(invoice: Invoice): void {
    reopenForm.post(reopenUpload(invoice.id).url, { preserveScroll: true });
}

function isOverdue(invoice: Invoice): boolean {
    return (
        ['unpaid', 'partial'].includes(invoice.status) &&
        new Date(invoice.due_date).getTime() < new Date().setHours(0, 0, 0, 0)
    );
}
</script>

<template>
    <Head title="Tagihan — Panel Admin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                Tagihan
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Pantau jatuh tempo, denda, dan status pembayaran.
            </p>
        </div>

        <form
            :action="invoicesIndex().url"
            method="GET"
            class="grid gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 sm:grid-cols-[1.4fr_1fr_1fr_auto] dark:border-white/10 dark:bg-zinc-900"
        >
            <label class="relative block">
                <span class="sr-only">Cari nomor tagihan</span>
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                <input
                    type="search"
                    name="q"
                    :value="filters.q"
                    placeholder="Nomor tagihan atau kode booking"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 pr-3 pl-9 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                />
            </label>

            <label class="block">
                <span class="sr-only">Cakupan</span>
                <select
                    name="scope"
                    :value="filters.scope"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                >
                    <option value="">Semua tagihan</option>
                    <option value="overdue">Lewat jatuh tempo</option>
                </select>
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
                Filter
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
            <div v-if="invoices.data.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200/80 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:border-white/10 dark:bg-white/5 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tagihan</th>
                            <th class="px-4 py-3 font-medium">Booking</th>
                            <th class="px-4 py-3 font-medium">Jatuh tempo</th>
                            <th class="px-4 py-3 font-medium">Nilai</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="transition hover:bg-zinc-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ invoice.number }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ invoice.booking.property.name }} · {{ invoice.booking.room_type.name }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="showBooking(invoice.booking.code)" class="font-mono text-xs text-emerald-600 hover:underline">
                                    {{ invoice.booking.code }}
                                </Link>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ invoice.booking.user.name }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="isOverdue(invoice) ? 'font-medium text-rose-600 dark:text-rose-400' : 'text-zinc-600 dark:text-zinc-300'">
                                    {{ formatDate(invoice.due_date) }}
                                </span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Toleransi {{ formatDate(invoice.grace_until) }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ formatRupiah(invoice.amount + invoice.late_fee) }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Dibayar {{ formatRupiah(invoice.paid_amount) }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="isOverdue(invoice) ? 'overdue' : invoice.status" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-600 hover:text-emerald-600 dark:text-zinc-300"
                                    @click="reopen(invoice)"
                                >
                                    <FileUp class="size-3.5" />
                                    Buka upload
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="px-5 py-16 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Belum ada tagihan yang cocok dengan filter.
            </p>
        </div>

        <PaginationLinks :links="invoices.links" />
    </div>
</template>
