<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays } from '@lucide/vue';
import SeoHead from '@/components/SeoHead.vue';
import { formatDate } from '@/lib/format';
import type { Page } from '@/types';
import { show as showPage } from '@/routes/pages';

const props = defineProps<{
    page: Page;
}>();

const paragraphs = props.page.body
    .split(/\n{2,}/)
    .map((paragraph) => paragraph.trim())
    .filter((paragraph) => paragraph.length > 0);
</script>

<template>
    <SeoHead
        :title="page.title"
        :description="`${page.title} KosanGo versi ${page.version}.`"
    />

    <div class="mx-auto w-full max-w-4xl px-4 py-12 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="showPage(page.slug)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-emerald-600 dark:text-zinc-400"
            >
                <ArrowLeft class="size-4" />
                {{ page.title }}
            </Link>
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-white/10 dark:text-zinc-300"
            >
                <CalendarDays class="size-3.5" />
                Versi {{ page.version }} · diperbarui
                {{ formatDate(page.updated_at) }}
            </span>
        </div>

        <article
            class="mt-6 rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm sm:p-10 dark:border-white/10 dark:bg-zinc-900"
        >
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                {{ page.title }}
            </h1>

            <div class="mt-6 space-y-4 leading-relaxed text-zinc-600 dark:text-zinc-300">
                <p v-for="(paragraph, index) in paragraphs" :key="index">
                    {{ paragraph }}
                </p>
            </div>
        </article>
    </div>
</template>
