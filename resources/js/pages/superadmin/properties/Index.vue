<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Building2, Eye, EyeOff, Pencil, Plus, Trash2 } from '@lucide/vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { Paginated, Property } from '@/types';
import { dashboard } from '@/routes/admin';
import {
    create,
    destroy,
    index as propertiesIndex,
    publish,
    edit as editProperty,
} from '@/routes/superadmin/properties';

defineProps<{
    properties: Paginated<Property>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Properti', href: propertiesIndex() },
        ],
    },
});

const publishForm = useForm({});
const deleteForm = useForm({});

function togglePublish(property: Property): void {
    publishForm.patch(publish(property.slug).url, { preserveScroll: true });
}

function remove(property: Property): void {
    deleteForm.delete(destroy(property.slug).url, { preserveScroll: true });
}

function typeLabel(property: Property): string {
    return property.type === 'hotel' ? 'Hotel' : 'Kos';
}
</script>

<template>
    <Head title="Properti — Superadmin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    Properti
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Kelola properti, tipe kamar, tarif, dan kamar sebelum dipublikasikan.
                </p>
            </div>
            <Link
                :href="create().url"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500"
            >
                <Plus class="size-4" />
                Properti baru
            </Link>
        </div>

        <div class="space-y-3">
            <article
                v-for="property in properties.data"
                :key="property.id"
                class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
                            <img
                                v-if="property.thumbnail_url"
                                :src="property.thumbnail_url"
                                :alt="property.thumbnail_alt ?? property.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-zinc-400"
                            >
                                <Building2 class="size-6" />
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate font-semibold text-zinc-900 dark:text-white">
                                    {{ property.name }}
                                </h2>
                                <StatusBadge :status="property.status" />
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ typeLabel(property) }} · {{ property.city?.name ?? '-' }} ·
                                {{ property.room_types_count ?? 0 }} tipe kamar ·
                                {{ property.rooms_count ?? 0 }} kamar
                            </p>
                            <p class="mt-1 line-clamp-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">
                                {{ property.address }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            :disabled="publishForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition disabled:opacity-60"
                            :class="
                                property.status === 'published'
                                    ? 'border-zinc-200 text-zinc-700 hover:border-amber-300 hover:text-amber-700 dark:border-white/10 dark:text-zinc-200'
                                    : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10'
                            "
                            @click="togglePublish(property)"
                        >
                            <component
                                :is="property.status === 'published' ? EyeOff : Eye"
                                class="size-3.5"
                            />
                            {{ property.status === 'published' ? 'Jadikan draft' : 'Publikasikan' }}
                        </button>
                        <Link
                            :href="editProperty(property.slug).url"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200"
                        >
                            <Pencil class="size-3.5" />
                            Kelola
                        </Link>
                        <button
                            type="button"
                            :disabled="deleteForm.processing"
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-60 dark:border-rose-500/20 dark:text-rose-400"
                            @click="remove(property)"
                        >
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </div>
            </article>

            <p
                v-if="properties.data.length === 0"
                class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center text-sm text-zinc-500 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-400"
            >
                Belum ada properti. Mulai dengan membuat properti baru.
            </p>
        </div>

        <div class="flex flex-col items-center gap-3">
            <p
                v-if="properties.total > 0"
                class="text-xs text-zinc-500 dark:text-zinc-400"
            >
                Menampilkan {{ properties.from }}-{{ properties.to }} dari
                {{ properties.total }} properti
            </p>
            <PaginationLinks :links="properties.links" />
        </div>
    </div>
</template>
