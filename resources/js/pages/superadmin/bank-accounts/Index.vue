<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Landmark, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import type { BankAccount } from '@/types';
import { dashboard } from '@/routes/admin';
import {
    destroy,
    index as accountsIndex,
    store,
    update,
} from '@/routes/superadmin/bank-accounts';

const props = defineProps<{
    accounts: BankAccount[];
    properties: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Rekening Bank', href: accountsIndex() },
        ],
    },
});

const editing = ref<BankAccount | null>(null);
const deleteForm = useForm({});

const form = useForm<{
    property_id: number | null;
    bank_name: string;
    account_number: string;
    account_holder: string;
    is_active: boolean;
}>({
    property_id: null,
    bank_name: '',
    account_number: '',
    account_holder: '',
    is_active: true,
});

function startEdit(account: BankAccount): void {
    editing.value = account;
    form.property_id = account.property_id;
    form.bank_name = account.bank_name;
    form.account_number = account.account_number;
    form.account_holder = account.account_holder;
    form.is_active = account.is_active;
}

function reset(): void {
    editing.value = null;
    form.reset();
}

function submit(): void {
    if (editing.value) {
        form.put(update(editing.value.id).url, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });

        return;
    }

    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function remove(account: BankAccount): void {
    deleteForm.delete(destroy(account.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Rekening Bank — Superadmin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                Rekening Bank
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Rekening tujuan transfer manual. Rekening tanpa properti berlaku global.
            </p>
        </div>

        <form
            class="grid gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 sm:grid-cols-2 xl:grid-cols-4 dark:border-white/10 dark:bg-zinc-900"
            @submit.prevent="submit"
        >
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Properti</span>
                <select
                    v-model="form.property_id"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                >
                    <option :value="null">Global (semua properti)</option>
                    <option v-for="property in properties" :key="property.id" :value="property.id">
                        {{ property.name }}
                    </option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama bank</span>
                <input v-model="form.bank_name" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.bank_name" class="mt-1 block text-xs text-rose-600">{{ form.errors.bank_name }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nomor rekening</span>
                <input v-model="form.account_number" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.account_number" class="mt-1 block text-xs text-rose-600">{{ form.errors.account_number }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama pemilik</span>
                <input v-model="form.account_holder" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.account_holder" class="mt-1 block text-xs text-rose-600">{{ form.errors.account_holder }}</span>
            </label>

            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <input v-model="form.is_active" type="checkbox" class="accent-emerald-600" />
                Aktif
            </label>

            <div class="flex items-center gap-2 xl:col-span-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Plus class="size-4" />
                    {{ editing ? 'Simpan' : 'Tambah rekening' }}
                </button>
                <button
                    v-if="editing"
                    type="button"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                    @click="reset"
                >
                    Batal
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
            <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                <li v-for="account in accounts" :key="account.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <Landmark class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ account.bank_name }} · {{ account.account_number }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ account.account_holder }} · {{ account.property?.name ?? 'Global' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="account.is_active
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                : 'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-300'"
                        >
                            {{ account.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <button type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" @click="startEdit(account)">
                            Ubah
                        </button>
                        <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400" @click="remove(account)">
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </li>
            </ul>
            <p v-if="accounts.length === 0" class="px-5 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Belum ada rekening bank.
            </p>
        </div>
    </div>
</template>
