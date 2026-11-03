<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, MapPin, Search } from '@lucide/vue';
import { computed } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import PropertyCard from '@/components/PropertyCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import { gradientForSlug } from '@/lib/accents';
import type { City, Paginated, Property } from '@/types';
import { index as propertiesIndex } from '@/routes/properties';

const props = defineProps<{
    city: City;
    properties: Paginated<Property>;
}>();

const total = computed(() => props.properties.total);
const description = computed(
    () =>
        `Temukan ${total.value} hotel dan kos terkurasi di ${props.city.name}, ${props.city.province}. Bandingkan tarif per periode dan pesan langsung di KosanGo.`,
);
</script>

<template>
    <SeoHead :title="`Hotel & kos di ${city.name}`" :description="description" />

    <section class="relative overflow-hidden border-b border-zinc-200/70 dark:border-white/10">
        <div
            class="absolute inset-0 bg-gradient-to-br opacity-90"
            :class="gradientForSlug(city.slug)"
        />
        <div class="relative mx-auto w-full max-w-6xl px-4 py-14 text-white sm:px-6">
            <Link
                :href="propertiesIndex()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-white/80 transition hover:text-white"
            >
                <ArrowLeft class="size-4" />
                Semua penginapan
            </Link>

            <p class="mt-6 flex items-center gap-1.5 text-sm text-white/80">
                <MapPin class="size-4" />
                {{ city.province }}
            </p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">
                Hotel & kos di {{ city.name }}
            </h1>
            <p class="mt-3 max-w-2xl text-sm text-white/85">
                {{ total }} penginapan terkurasi dengan tarif transparan per tipe
                kamar. Pilih periode harian, bulanan, atau tahunan sesuai
                kebutuhan.
            </p>

            <Link
                :href="propertiesIndex.url({ query: { city: city.slug } })"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-emerald-50"
            >
                <Search class="size-4" />
                Cari di {{ city.name }}
            </Link>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6">
        <div
            v-if="properties.data.length > 0"
            class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
        >
            <PropertyCard
                v-for="property in properties.data"
                :key="property.id"
                :property="property"
            />
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center dark:border-white/15 dark:bg-zinc-900"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
            >
                <Building2 class="size-6" />
            </span>
            <h2 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                Belum ada penginapan di {{ city.name }}
            </h2>
            <p class="mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                Kami sedang menambahkan pilihan baru. Sementara itu, lihat kota
                lain yang sudah tersedia.
            </p>
            <Link
                :href="propertiesIndex()"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
            >
                <MapPin class="size-4" />
                Lihat semua penginapan
            </Link>
        </div>

        <div v-if="properties.data.length > 0" class="mt-10">
            <PaginationLinks :links="properties.links" />
        </div>
    </section>
</template>
