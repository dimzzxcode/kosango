<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Check, FileUp, Lock, X } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';
import { store } from '@/routes/payments';
import type { BankAccount, Invoice } from '@/types';

const props = defineProps<{
    bookingCode: string;
    invoice: Invoice;
    banks: BankAccount[];
}>();

const emit = defineEmits<{
    uploaded: [];
}>();

const page = usePage();
const rules = computed(() => page.props.bookingRules);

const remaining = computed(() =>
    Math.max(
        0,
        props.invoice.amount +
            props.invoice.late_fee -
            props.invoice.paid_amount,
    ),
);

const minimum = computed(() => {
    if (!props.invoice.allow_partial || props.invoice.paid_amount > 0) {
        return 1;
    }

    return Math.min(
        Math.ceil((props.invoice.amount * rules.value.minDownPaymentPercent) / 100),
        remaining.value,
    );
});

const uploadLocked = computed(() => {
    if (!props.invoice.payments) {
        return false;
    }

    const resetAt = props.invoice.upload_reset_at
        ? new Date(props.invoice.upload_reset_at).getTime()
        : 0;

    const rejected = props.invoice.payments.filter(
        (payment) =>
            payment.status === 'rejected' &&
            new Date(payment.created_at).getTime() >= resetAt,
    );

    return rejected.length >= rules.value.maxRejectedUploads;
});

const form = useForm<{
    amount: number;
    bank_account_id: number | null;
    proof: File | null;
}>({
    amount: remaining.value,
    bank_account_id: props.banks[0]?.id ?? null,
    proof: null,
});

function pickFile(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.proof = target.files?.[0] ?? null;
}

function submit(): void {
    form.post(
        store({
            booking: props.bookingCode,
            invoice: props.invoice.id,
        }).url,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('proof');
                emit('uploaded');
            },
        },
    );
}
</script>

<template>
    <div
        v-if="uploadLocked"
        class="mt-4 flex items-start gap-3 rounded-xl bg-rose-50 p-4 text-sm text-rose-800 dark:bg-rose-500/10 dark:text-rose-300"
    >
        <Lock class="mt-0.5 size-4 shrink-0" />
        <div>
            <p class="font-medium">Upload bukti dikunci.</p>
            <p class="mt-1">
                Sudah {{ rules.maxRejectedUploads }} kali bukti ditolak. Silakan
                hubungi admin properti untuk membuka kembali.
            </p>
        </div>
    </div>

    <form
        v-else
        class="mt-4 space-y-3 rounded-xl bg-zinc-50 p-4 dark:bg-white/5"
        @submit.prevent="submit"
    >
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                    Nominal transfer
                </span>
                <input
                    v-model.number="form.amount"
                    type="number"
                    :min="minimum"
                    :max="remaining"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-zinc-900"
                />
                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                    Sisa tagihan {{ formatRupiah(remaining) }}
                    <template v-if="minimum > 1">
                        · minimum pertama {{ formatRupiah(minimum) }}
                    </template>
                </span>
                <InputError class="mt-1" :message="form.errors.amount" />
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                    Rekening tujuan
                </span>
                <select
                    v-model="form.bank_account_id"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-zinc-900"
                >
                    <option :value="null">Pilih rekening</option>
                    <option
                        v-for="bank in banks"
                        :key="bank.id"
                        :value="bank.id"
                    >
                        {{ bank.bank_name }} · {{ bank.account_number }} ·
                        {{ bank.account_holder }}
                    </option>
                </select>
                <InputError class="mt-1" :message="form.errors.bank_account_id" />
            </label>
        </div>

        <label
            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 bg-white px-4 py-6 text-center transition hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-white/15 dark:bg-zinc-900 dark:hover:border-emerald-500/60 dark:hover:bg-emerald-500/5"
        >
            <FileUp class="size-5 text-emerald-600" />
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ form.proof ? form.proof.name : 'Pilih bukti transfer' }}
            </span>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                JPG, PNG, atau WEBP maksimal 2 MB
            </span>
            <input
                type="file"
                class="sr-only"
                accept=".jpg,.jpeg,.png,.webp"
                @change="pickFile"
            />
        </label>

        <InputError :message="form.errors.proof" />
        <InputError :message="page.props.errors.invoice" />
        <InputError :message="page.props.errors.booking" />

        <div class="flex items-center gap-2">
            <Button type="submit" :disabled="form.processing || !form.proof">
                <Check class="size-4" />
                {{ form.processing ? 'Mengunggah...' : 'Kirim Bukti Transfer' }}
            </Button>
            <Button type="button" variant="ghost" size="sm" @click="emit('uploaded')">
                <X class="size-4" />
                Batal
            </Button>
        </div>
    </form>
</template>
