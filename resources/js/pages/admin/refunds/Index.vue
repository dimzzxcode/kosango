<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FileUp, RotateCcw, X } from '@lucide/vue';
import { ref } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatDate, formatRupiah } from '@/lib/format';
import type { Paginated, Refund } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as refundsIndex, process, reject } from '@/routes/admin/refunds';
import { show as showBooking } from '@/routes/admin/bookings';

type RefundRow = Refund & {
    booking: {
        code: string;
        property: { name: string };
        user: { name: string };
    };
};

const props = defineProps<{
    refunds: Paginated<RefundRow>;
    filters: {
        status?: string;
    };
    statuses: Array<{ value: string; label: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Refund', href: refundsIndex() },
        ],
    },
});

const activeRefund = ref<number | null>(null);
const rejecting = ref<number | null>(null);

const processForm = useForm<{ proof: File | null }>({ proof: null });
const rejectForm = useForm<{ reason: string }>({ reason: '' });

function pickProof(event: Event): void {
    const target = event.target as HTMLInputElement;
    processForm.proof = target.files?.[0] ?? null;
}

function submitProcess(refund: Refund): void {
    processForm.post(process(refund.id).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            processForm.reset();
            activeRefund.value = null;
        },
    });
}

function startReject(refund: Refund): void {
    activeRefund.value = null;
    rejecting.value = refund.id;
    rejectForm.reset();
}

function submitReject(refund: Refund): void {
    rejectForm.post(reject(refund.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            rejecting.value = null;
            rejectForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Refund — Panel Admin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                Refund
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Proses pengembalian dana dan unggah bukti transfer.
            </p>
        </div>

        <form
            :action="refundsIndex().url"
            method="GET"
            class="flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
        >
            <label class="block">
                <span class="sr-only">Status</span>
                <select
                    name="status"
                    :value="filters.status"
                    class="h-10 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                >
                    <option value="">Semua status</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </label>
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500">
                Filter
            </button>
        </form>

        <div class="space-y-3">
            <article
                v-for="refund in refunds.data"
                :key="refund.id"
                class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link :href="showBooking(refund.booking.code)" class="font-mono text-sm font-medium text-emerald-600 hover:underline">
                                {{ refund.booking.code }}
                            </Link>
                            <StatusBadge :status="refund.status" />
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ refund.initiated_by === 'customer' ? 'diajukan customer' : 'dibatalkan admin' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ refund.booking.property.name }} · {{ refund.booking.user.name }}
                        </p>
                        <p v-if="refund.bank_name" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ refund.bank_name }} · {{ refund.account_number }} · {{ refund.account_holder }}
                        </p>
                        <p v-else class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                            Customer belum mengisi rekening.
                        </p>
                        <p v-if="refund.rejection_reason" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                            Ditolak: {{ refund.rejection_reason }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-zinc-900 dark:text-white">{{ formatRupiah(refund.amount) }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ formatDate(refund.created_at) }}</p>
                    </div>
                </div>

                <div v-if="refund.status === 'requested'" class="mt-3 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500"
                        @click="activeRefund = activeRefund === refund.id ? null : refund.id"
                    >
                        <RotateCcw class="size-3.5" />
                        Proses refund
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400 dark:hover:bg-rose-500/10"
                        @click="startReject(refund)"
                    >
                        <X class="size-3.5" />
                        Tolak
                    </button>
                </div>

                <form
                    v-if="activeRefund === refund.id"
                    class="mt-3 space-y-2 rounded-xl bg-zinc-50 p-3 dark:bg-white/5"
                    @submit.prevent="submitProcess(refund)"
                >
                    <label class="block cursor-pointer">
                        <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                            Bukti transfer refund
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-lg border border-dashed border-zinc-300 px-3 py-2 text-sm dark:border-white/15">
                            <FileUp class="size-4 text-emerald-600" />
                            {{ processForm.proof ? processForm.proof.name : 'Pilih file bukti' }}
                        </span>
                        <input type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp" @change="pickProof" />
                    </label>
                    <span v-if="processForm.errors.proof" class="block text-xs text-rose-600">
                        {{ processForm.errors.proof }}
                    </span>
                    <button
                        type="submit"
                        :disabled="processForm.processing || !processForm.proof"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                    >
                        Tandai selesai
                    </button>
                </form>

                <form
                    v-if="rejecting === refund.id"
                    class="mt-3 space-y-2 rounded-xl bg-rose-50/60 p-3 dark:bg-rose-500/5"
                    @submit.prevent="submitReject(refund)"
                >
                    <input
                        v-model="rejectForm.reason"
                        type="text"
                        placeholder="Alasan penolakan (wajib)"
                        class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                    />
                    <span v-if="rejectForm.errors.reason" class="block text-xs text-rose-600">
                        {{ rejectForm.errors.reason }}
                    </span>
                    <button
                        type="submit"
                        :disabled="rejectForm.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-500 disabled:opacity-60"
                    >
                        Kirim penolakan
                    </button>
                </form>

                <a
                    v-if="refund.proof_url"
                    :href="refund.proof_url"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 hover:underline"
                >
                    Lihat bukti refund
                </a>
            </article>

            <div
                v-if="refunds.data.length === 0"
                class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center text-sm text-zinc-500 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-400"
            >
                Belum ada refund.
            </div>
        </div>

        <PaginationLinks :links="refunds.links" />
    </div>
</template>
