<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Search, Star, Trash2 } from '@lucide/vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { formatDate } from '@/lib/format';
import type { Paginated, Review } from '@/types';
import { dashboard } from '@/routes/admin';
import { destroy, index as reviewsIndex, visibility } from '@/routes/superadmin/reviews';

const props = defineProps<{
    reviews: Paginated<Review>;
    filters: {
        visibility?: string;
        q?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Ulasan', href: reviewsIndex() },
        ],
    },
});

const toggleForm = useForm({});
const deleteForm = useForm({});

function toggle(review: Review): void {
    toggleForm.patch(visibility(review.id).url, { preserveScroll: true });
}

function remove(review: Review): void {
    deleteForm.delete(destroy(review.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Ulasan — Superadmin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Ulasan</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Moderasi ulasan tamu sebelum tampil di halaman properti.
            </p>
        </div>

        <form :action="reviewsIndex().url" method="GET" class="grid gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 sm:grid-cols-[1.4fr_1fr_auto] dark:border-white/10 dark:bg-zinc-900">
            <label class="relative block">
                <span class="sr-only">Cari</span>
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                <input
                    type="search"
                    name="q"
                    :value="filters.q"
                    placeholder="Nama properti atau tamu"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 pr-3 pl-9 text-sm dark:border-white/10 dark:bg-white/5"
                />
            </label>
            <label class="block">
                <span class="sr-only">Visibilitas</span>
                <select name="visibility" :value="filters.visibility" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5">
                    <option value="">Semua</option>
                    <option value="visible">Tampil</option>
                    <option value="hidden">Disembunyikan</option>
                </select>
            </label>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500">
                Filter
            </button>
        </form>

        <div class="space-y-3">
            <article v-for="review in reviews.data" :key="review.id" class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-zinc-900 dark:text-white">{{ review.property?.name }}</p>
                            <span class="flex items-center gap-0.5">
                                <Star v-for="index in 5" :key="index" class="size-3.5" :class="index <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-zinc-300 dark:text-zinc-600'" />
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="review.is_visible ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-300'"
                            >
                                {{ review.is_visible ? 'Tampil' : 'Disembunyikan' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ review.user?.name }} · booking {{ review.booking?.code }} · {{ formatDate(review.created_at) }}
                        </p>
                        <p v-if="review.comment" class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ review.comment }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200"
                            @click="toggle(review)"
                        >
                            <component :is="review.is_visible ? EyeOff : Eye" class="size-3.5" />
                            {{ review.is_visible ? 'Sembunyikan' : 'Tampilkan' }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400"
                            @click="remove(review)"
                        >
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </div>
            </article>

            <p v-if="reviews.data.length === 0" class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center text-sm text-zinc-500 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-400">
                Belum ada ulasan.
            </p>
        </div>

        <PaginationLinks :links="reviews.links" />
    </div>
</template>
