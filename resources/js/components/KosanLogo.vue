<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { home } from '@/routes';

withDefaults(
    defineProps<{
        inverted?: boolean;
    }>(),
    {
        inverted: false,
    },
);
</script>

<template>
    <Link :href="home()" class="flex items-center gap-2.5">
        <span
            class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm shadow-emerald-500/30"
        >
            <Building2 class="size-5" />
        </span>
        <span
            class="text-lg font-semibold tracking-tight"
            :class="inverted ? 'text-white' : 'text-zinc-900 dark:text-white'"
        >
            Kosan<span class="text-emerald-500">Go</span>
        </span>
    </Link>
</template>
