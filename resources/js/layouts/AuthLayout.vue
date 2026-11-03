<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <Head>
            <meta head-key="robots" name="robots" content="noindex, nofollow" />
        </Head>
        <slot />
    </AuthLayout>
</template>
