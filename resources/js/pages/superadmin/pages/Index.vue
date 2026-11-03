<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileText, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import type { Page } from '@/types';
import { dashboard } from '@/routes/admin';
import { destroy, index as pagesIndex, store, update } from '@/routes/superadmin/pages';

defineProps<{
    pages: Page[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Halaman', href: pagesIndex() },
        ],
    },
});

const editing = ref<Page | null>(null);
const deleteForm = useForm({});

const form = useForm({ slug: '', title: '', body: '' });

function startEdit(page: Page): void {
    editing.value = page;
    form.slug = page.slug;
    form.title = page.title;
    form.body = page.body;
}

function reset(): void {
    editing.value = null;
    form.reset();
}

function submit(): void {
    if (editing.value) {
        form.put(update(editing.value.slug).url, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });

        return;
    }

    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => reset(),
    });
}

function remove(page: Page): void {
    deleteForm.delete(destroy(page.slug).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Halaman — Superadmin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Halaman</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Syarat & ketentuan, kebijakan privasi, dan halaman statis lain.
            </p>
        </div>

        <form
            class="space-y-3 rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
            @submit.prevent="submit"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Slug</span>
                    <input v-model="form.slug" type="text" placeholder="syarat-ketentuan" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                    <span v-if="form.errors.slug" class="mt-1 block text-xs text-rose-600">{{ form.errors.slug }}</span>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Judul</span>
                    <input v-model="form.title" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                    <span v-if="form.errors.title" class="mt-1 block text-xs text-rose-600">{{ form.errors.title }}</span>
                </label>
            </div>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Isi halaman</span>
                <textarea v-model="form.body" rows="6" class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.body" class="mt-1 block text-xs text-rose-600">{{ form.errors.body }}</span>
            </label>
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Plus class="size-4" />
                    {{ editing ? 'Simpan perubahan' : 'Buat halaman' }}
                </button>
                <button v-if="editing" type="button" class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300" @click="reset">
                    Batal
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
            <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                <li v-for="page in pages" :key="page.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <FileText class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ page.title }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                /halaman/{{ page.slug }} · versi {{ page.version }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" @click="startEdit(page)">
                            Ubah
                        </button>
                        <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400" @click="remove(page)">
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </li>
            </ul>
            <p v-if="pages.length === 0" class="px-5 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Belum ada halaman.
            </p>
        </div>
    </div>
</template>
