<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, MapPin, Search, SlidersHorizontal, X } from '@lucide/vue';
import { computed } from 'vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import PropertyCard from '@/components/PropertyCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import type { City, Paginated, Property } from '@/types';
import { index as propertiesIndex } from '@/routes/properties';

const props = defineProps<{
    properties: Paginated<Property>;
    cities: City[];
    filters: {
        city?: string;
        type?: string;
        q?: string;
        guests?: string;
        period?: string;
        min_price?: string;
        max_price?: string;
    };
}>();

const hasFilters = computed(() => {
    const { city, type, q, guests, period, min_price, max_price } =
        props.filters;

    return Boolean(
        city || type || q || guests || period || min_price || max_price,
    );
});

const resultLabel = computed(() => {
    if (props.properties.total === 0) {
        return 'Tidak ada penginapan yang cocok';
    }

    return `${props.properties.total} penginapan ditemukan`;
});

const pageTitle = computed(() => {
    const city = props.cities.find((item) => item.slug === props.filters.city);

    if (city) {
        return `Kos & hotel di ${city.name}`;
    }

    if (props.filters.type === 'kos') {
        return 'Cari kos';
    }

    if (props.filters.type === 'hotel') {
        return 'Cari hotel';
    }

    return 'Cari penginapan';
});

const pageDescription = computed(() => {
    const city = props.cities.find((item) => item.slug === props.filters.city);
    const location = city ? ` di ${city.name}` : ' di Indonesia';

    return `Bandingkan ${props.properties.total} hotel dan kos terkurasi${location}. Lihat tarif harian, bulanan, dan tahunan, lalu pesan langsung lewat KosanGo.`;
});
</script>

<template>
    <SeoHead :title="pageTitle" :description="pageDescription" />

    <section
        class="border-b border-zinc-200/70 bg-gradient-to-b from-emerald-50/70 to-transparent dark:border-white/10 dark:from-emerald-500/5"
    >
        <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                Direktori KosanGo
            </p>
            <h1
                class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900 sm:text-4xl dark:text-white"
            >
                Cari hotel & kos di seluruh kota
            </h1>
            <p class="mt-3 max-w-2xl text-zinc-600 dark:text-zinc-300">
                Filter berdasarkan kota, tipe penginapan, periode sewa,
                rentang harga, atau jumlah tamu. Tarif ditampilkan per periode
                untuk tipe kamar paling ekonomis.
            </p>

            <form
                :action="propertiesIndex().url"
                method="GET"
                class="mt-8 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-lg shadow-zinc-900/5 sm:p-4 dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    <label class="relative block sm:col-span-2 lg:col-span-3">
                        <span class="sr-only">Cari nama atau alamat</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400"
                        />
                        <input
                            type="search"
                            name="q"
                            :value="filters.q"
                            placeholder="Nama hotel, kos, atau alamat"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 pr-3 pl-9 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        />
                    </label>

                    <label class="block">
                        <span class="sr-only">Kota</span>
                        <select
                            name="city"
                            :value="filters.city"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        >
                            <option value="">Semua kota</option>
                            <option
                                v-for="city in cities"
                                :key="city.id"
                                :value="city.slug"
                            >
                                {{ city.name }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="sr-only">Tipe</span>
                        <select
                            name="type"
                            :value="filters.type"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        >
                            <option value="">Semua tipe</option>
                            <option value="hotel">Hotel</option>
                            <option value="kos">Kos</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="sr-only">Jumlah tamu</span>
                        <input
                            type="number"
                            name="guests"
                            min="1"
                            max="30"
                            :value="filters.guests"
                            placeholder="Tamu"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        />
                    </label>

                    <label class="block lg:col-span-2">
                        <span class="sr-only">Periode sewa</span>
                        <select
                            name="period"
                            :value="filters.period"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        >
                            <option value="">Semua periode</option>
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </label>

                    <label class="block lg:col-span-2">
                        <span class="sr-only">Harga minimum</span>
                        <input
                            type="number"
                            name="min_price"
                            min="0"
                            step="10000"
                            :value="filters.min_price"
                            placeholder="Harga min (Rp)"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        />
                    </label>

                    <label class="block">
                        <span class="sr-only">Harga maksimum</span>
                        <input
                            type="number"
                            name="max_price"
                            min="0"
                            step="10000"
                            :value="filters.max_price"
                            placeholder="Harga maks (Rp)"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        />
                    </label>

                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-500"
                    >
                        <SlidersHorizontal class="size-4" />
                        Terapkan
                    </button>
                </div>

                <div
                    v-if="hasFilters"
                    class="mt-3 flex items-center gap-2 text-sm"
                >
                    <Link
                        :href="propertiesIndex()"
                        class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-zinc-600 hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-300"
                    >
                        <X class="size-3.5" />
                        Reset filter
                    </Link>
                </div>
            </form>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                {{ resultLabel }}
            </h2>
            <p
                v-if="properties.from && properties.to"
                class="text-sm text-zinc-500 dark:text-zinc-400"
            >
                Menampilkan {{ properties.from }}–{{ properties.to }} dari
                {{ properties.total }}
            </p>
        </div>

        <div
            v-if="properties.data.length > 0"
            class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
        >
            <PropertyCard
                v-for="property in properties.data"
                :key="property.id"
                :property="property"
                :period="filters.period"
            />
        </div>

        <div
            v-else
            class="mt-8 flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center dark:border-white/15 dark:bg-zinc-900"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
            >
                <Building2 class="size-6" />
            </span>
            <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                Belum ada penginapan yang cocok
            </h3>
            <p class="mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                Coba ubah kota, tipe penginapan, atau jumlah tamu. Kamu juga
                bisa melihat semua pilihan yang tersedia.
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
