<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1.5"
        aria-label="Navigasi halaman"
    >
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="flex min-w-9 items-center justify-center rounded-lg border px-3 py-1.5 text-sm transition"
                :class="
                    link.active
                        ? 'border-emerald-600 bg-emerald-600 text-white'
                        : 'border-zinc-200 bg-white text-zinc-600 hover:border-emerald-300 hover:text-emerald-700 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300'
                "
                v-html="link.label"
            />
            <span
                v-else
                class="flex min-w-9 items-center justify-center rounded-lg border border-zinc-100 px-3 py-1.5 text-sm text-zinc-300 dark:border-white/5 dark:text-zinc-600"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
