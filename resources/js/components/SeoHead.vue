<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, h } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string | null;
        image?: string | null;
        type?: 'website' | 'article';
        noindex?: boolean;
        jsonLd?: Record<string, unknown> | null;
    }>(),
    {
        description: null,
        image: null,
        type: 'website',
        noindex: false,
        jsonLd: null,
    },
);

const page = usePage();

const canonical = computed(() => page.props.canonical);
const siteName = computed(() => page.props.name);
const metaDescription = computed(() => props.description ?? '');
const robots = computed(() =>
    props.noindex ? 'noindex, nofollow' : 'index, follow',
);
const structuredData = computed(() =>
    props.jsonLd
        ? JSON.stringify(props.jsonLd).replace(/</g, '\\u003c')
        : null,
);

/**
 * Vue's DOM template compiler drops <script> tags declared in templates,
 * so the JSON-LD node is built with a render function instead. Inertia's
 * <Head> resolves function vnodes and renders them into the document head.
 */
const JsonLd = () =>
    h(
        'script',
        { type: 'application/ld+json', 'head-key': 'ld-json' },
        structuredData.value ?? '',
    );
</script>

<template>
    <Head :title="title">
        <meta
            head-key="description"
            name="description"
            :content="metaDescription"
        />
        <meta head-key="robots" name="robots" :content="robots" />
        <link head-key="canonical" rel="canonical" :href="canonical" />

        <meta head-key="og:type" property="og:type" :content="type" />
        <meta head-key="og:site_name" property="og:site_name" :content="siteName" />
        <meta head-key="og:title" property="og:title" :content="title" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="metaDescription"
        />
        <meta head-key="og:url" property="og:url" :content="canonical" />
        <meta v-if="image" head-key="og:image" property="og:image" :content="image" />

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="title" />
        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="metaDescription"
        />

        <JsonLd v-if="structuredData" />
    </Head>
</template>
