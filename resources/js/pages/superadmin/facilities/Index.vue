<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Sparkles, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import type { Facility } from '@/types';
import { dashboard } from '@/routes/admin';
import { destroy, index as facilitiesIndex, store, update } from '@/routes/superadmin/facilities';

defineProps<{
    facilities: Facility[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Fasilitas', href: facilitiesIndex() },
        ],
    },
});

const editing = ref<Facility | null>(null);
const form = useForm({ name: '', icon: '' });

function startEdit(facility: Facility): void {
    editing.value = facility;
    form.name = facility.name;
    form.icon = facility.icon ?? '';
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

function remove(facility: Facility): void {
    useForm({}).delete(destroy(facility.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Fasilitas — Superadmin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Fasilitas</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Daftar fasilitas yang bisa dipilih saat mengelola properti.
            </p>
        </div>

        <form
            class="grid gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 sm:grid-cols-[1.2fr_1fr_auto_auto] dark:border-white/10 dark:bg-zinc-900"
            @submit.prevent="submit"
        >
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama fasilitas</span>
                <input v-model="form.name" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.name" class="mt-1 block text-xs text-rose-600">{{ form.errors.name }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Ikon (opsional)</span>
                <input v-model="form.icon" type="text" placeholder="wifi" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
            </label>
            <button
                type="submit"
                :disabled="form.processing"
                class="mt-6 inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
            >
                <Plus class="size-4" />
                {{ editing ? 'Simpan' : 'Tambah' }}
            </button>
            <button
                v-if="editing"
                type="button"
                class="mt-6 inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                @click="reset"
            >
                Batal
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
            <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                <li v-for="facility in facilities" :key="facility.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <Sparkles class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ facility.name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ facility.properties_count ?? 0 }} properti · ikon {{ facility.icon ?? '-' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" @click="startEdit(facility)">
                            Ubah
                        </button>
                        <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400" @click="remove(facility)">
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </li>
            </ul>
            <p v-if="facilities.length === 0" class="px-5 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Belum ada fasilitas.
            </p>
        </div>
    </div>
</template>
