<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarDays,
    CheckCircle2,
    CreditCard,
    MapPin,
    Search,
    ShieldCheck,
    Sparkles,
    Star,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import PropertyCard from '@/components/PropertyCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import { gradientForSlug, softFor } from '@/lib/accents';
import { formatRupiah, startingPrice } from '@/lib/format';
import type { City, Page, Property } from '@/types';
import { index as propertiesIndex } from '@/routes/properties';
import { show as showCity } from '@/routes/cities';

const props = defineProps<{
    cities: City[];
    allCities: Array<Pick<City, 'id' | 'name' | 'slug'>>;
    properties: Property[];
    stats: {
        cities: number;
        properties: number;
        bookings: number;
    };
    pages: Page[];
}>();

const cheapest = computed(() => {
    const rates = props.properties
        .map((property) => startingPrice(property))
        .filter((rate): rate is { price: number; unit: string } => rate !== null);

    if (rates.length === 0) {
        return null;
    }

    return rates.sort((a, b) => a.price - b.price)[0];
});
</script>

<template>
    <SeoHead
        title="Booking hotel & kos multi-kota"
        description="KosanGo membantu Anda menemukan hotel dan kos terkurasi di banyak kota Indonesia. Bandingkan tarif harian, bulanan, dan tahunan lalu pesan langsung."
    />

    <section class="relative overflow-hidden">
        <div
            class="pointer-events-none absolute -top-40 -right-24 size-[32rem] rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-500/10"
        />
        <div
            class="pointer-events-none absolute top-20 -left-32 size-[26rem] rounded-full bg-amber-200/30 blur-3xl dark:bg-amber-500/5"
        />

        <div
            class="relative mx-auto w-full max-w-6xl px-4 pt-14 pb-14 sm:px-6 lg:pt-24"
        >
            <div class="max-w-3xl">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    <Sparkles class="size-3.5" />
                    {{ stats.cities }} kota · {{ stats.properties }} penginapan
                    terkurasi
                </span>

                <h1
                    class="mt-6 text-4xl leading-[1.08] font-semibold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl dark:text-white"
                >
                    Kos dan hotel yang pas,
                    <span class="text-emerald-600 dark:text-emerald-400">
                        di kota mana pun.
                    </span>
                </h1>

                <p
                    class="mt-5 max-w-2xl text-base leading-relaxed text-zinc-600 sm:text-lg dark:text-zinc-300"
                >
                    Bandingkan tarif per tipe kamar per periode, pilih lunas,
                    bayar per periode, atau cicilan, lalu unggah bukti transfer.
                    Semua transparan, dari denda sampai refund.
                </p>
            </div>

            <form
                :action="propertiesIndex().url"
                method="GET"
                class="mt-10 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-xl shadow-zinc-900/5 sm:p-4 dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="grid gap-3 md:grid-cols-[1.2fr_1fr_0.8fr_auto]">
                    <label class="block">
                        <span
                            class="mb-1.5 flex items-center gap-1.5 px-1 text-xs font-medium text-zinc-500 dark:text-zinc-400"
                        >
                            <MapPin class="size-3.5" />
                            Kota tujuan
                        </span>
                        <select
                            name="city"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        >
                            <option value="">Semua kota</option>
                            <option
                                v-for="city in allCities"
                                :key="city.id"
                                :value="city.slug"
                            >
                                {{ city.name }}
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span
                            class="mb-1.5 flex items-center gap-1.5 px-1 text-xs font-medium text-zinc-500 dark:text-zinc-400"
                        >
                            <Building2 class="size-3.5" />
                            Tipe penginapan
                        </span>
                        <select
                            name="type"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        >
                            <option value="">Hotel & kos</option>
                            <option value="hotel">Hotel</option>
                            <option value="kos">Kos</option>
                        </select>
                    </label>

                    <label class="block">
                        <span
                            class="mb-1.5 flex items-center gap-1.5 px-1 text-xs font-medium text-zinc-500 dark:text-zinc-400"
                        >
                            <CalendarDays class="size-3.5" />
                            Tamu
                        </span>
                        <input
                            type="number"
                            name="guests"
                            min="1"
                            max="30"
                            placeholder="1 tamu"
                            class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none transition focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                        />
                    </label>

                    <button
                        type="submit"
                        class="mt-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500 md:mt-0"
                    >
                        <Search class="size-4" />
                        Cari
                    </button>
                </div>
            </form>

            <div
                class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-zinc-500 dark:text-zinc-400"
            >
                <span class="flex items-center gap-2">
                    <Wallet class="size-4 text-emerald-600" />
                    <template v-if="cheapest">
                        Mulai {{ formatRupiah(cheapest.price) }} /
                        {{ cheapest.unit }}
                    </template>
                    <template v-else>Tarif transparan per periode</template>
                </span>
                <span class="flex items-center gap-2">
                    <CreditCard class="size-4 text-emerald-600" />
                    Lunas, per periode, atau cicilan
                </span>
                <span class="flex items-center gap-2">
                    <ShieldCheck class="size-4 text-emerald-600" />
                    Refund penuh dalam 3 hari setelah konfirmasi
                </span>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                    Jelajahi kota
                </p>
                <h2
                    class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-white"
                >
                    Pilihan populer minggu ini
                </h2>
            </div>
            <Link
                :href="propertiesIndex()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-700 hover:text-emerald-600 dark:text-zinc-300"
            >
                Lihat semua penginapan
                <ArrowRight class="size-4" />
            </Link>
        </div>

        <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="city in cities"
                :key="city.id"
                :href="showCity(city.slug)"
                class="group relative overflow-hidden rounded-2xl bg-gradient-to-br p-6 text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-xl"
                :class="gradientForSlug(city.slug)"
            >
                <div
                    class="absolute -top-12 -right-10 size-40 rounded-full border border-white/20 bg-white/10"
                />
                <div
                    class="absolute -bottom-20 -left-6 size-40 rounded-full bg-black/10"
                />
                <div class="relative">
                    <span
                        class="inline-flex rounded-full border border-white/25 bg-white/15 px-2.5 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        {{ city.province }}
                    </span>
                    <h3 class="mt-8 text-2xl font-semibold">{{ city.name }}</h3>
                    <p class="mt-1 line-clamp-1 text-sm text-white/80">
                        Hotel dan kos pilihan di {{ city.name }}
                    </p>
                    <div
                        class="mt-5 flex items-center justify-between text-sm"
                    >
                        <span class="text-white/80">
                            {{ city.properties_count }} penginapan
                        </span>
                        <span
                            class="flex size-9 items-center justify-center rounded-full bg-white/15 transition group-hover:bg-white group-hover:text-zinc-900"
                        >
                            <ArrowRight class="size-4" />
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    </section>

    <section
        class="border-y border-zinc-200/70 bg-white py-16 dark:border-white/10 dark:bg-zinc-950/40"
    >
        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        Rekomendasi
                    </p>
                    <h2
                        class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-white"
                    >
                        Penginapan dengan rating tertinggi
                    </h2>
                </div>
                <Link
                    :href="propertiesIndex.url({ query: { type: 'hotel' } })"
                    class="text-sm font-medium text-zinc-600 hover:text-emerald-600 dark:text-zinc-300"
                >
                    Lihat hotel saja
                </Link>
            </div>

            <div
                v-if="properties.length > 0"
                class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
            >
                <PropertyCard
                    v-for="property in properties"
                    :key="property.id"
                    :property="property"
                />
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-center">
            <div>
                <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                    Cara kerja
                </p>
                <h2
                    class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl dark:text-white"
                >
                    Dari cari kamar sampai refund, semuanya jelas.
                </h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-300">
                    KosanGo dirancang untuk perjalanan singkat maupun tinggal
                    lebih lama. Setiap tagihan punya jatuh tempo, dan setiap
                    denda dihitung terbuka.
                </p>

                <div class="mt-7 space-y-4">
                    <div
                        v-for="(step, index) in [
                            {
                                title: 'Pilih kota dan tipe kamar',
                                description:
                                    'Bandingkan tarif per periode, kapasitas, dan fasilitas sebelum memesan.',
                            },
                            {
                                title: 'Tentukan cara pembayaran',
                                description:
                                    'Bayar lunas, per periode, atau cicilan dengan jadwal tagihan otomatis.',
                            },
                            {
                                title: 'Upload bukti transfer',
                                description:
                                    'Setiap tagihan punya tombol upload. Bukti diverifikasi admin properti.',
                            },
                            {
                                title: 'Denda & refund transparan',
                                description:
                                    'Denda Rp 50.000 setelah toleransi 7 hari. Refund dihitung server sesuai kebijakan.',
                            },
                        ]"
                        :key="step.title"
                        class="flex gap-4"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl text-sm font-semibold"
                            :class="softFor('jade')"
                        >
                            {{ index + 1 }}
                        </span>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{ step.title }}
                            </p>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ step.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <CreditCard class="size-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">
                        Pembayaran fleksibel
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Lunas sekaligus, per periode sewa, atau cicilan tahunan
                        dengan pembayaran pertama minimal 50%.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <CheckCircle2 class="size-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">
                        Denda transparan
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Lewat jatuh tempo? Denda Rp 50.000 dikenakan sekali per
                        tagihan setelah masa toleransi 7 hari.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300"
                    >
                        <ShieldCheck class="size-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">
                        Refund terarah
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Refund penuh pokok sewa bila dibatalkan maksimal 3 hari
                        setelah konfirmasi dan belum check-in.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"
                    >
                        <Star class="size-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">
                        Pilihan terkurasi
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        Hotel dan kos dengan rating serta fasilitas yang bisa
                        dibandingkan langsung.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 pb-20 sm:px-6">
        <div
            class="relative overflow-hidden rounded-3xl bg-zinc-900 px-6 py-12 text-white sm:px-12"
        >
            <div
                class="absolute -top-24 -right-20 size-72 rounded-full bg-emerald-500/25 blur-2xl"
            />
            <div
                class="absolute -bottom-24 left-10 size-64 rounded-full bg-teal-400/15 blur-2xl"
            />
            <div
                class="relative flex flex-col items-start justify-between gap-6 md:flex-row md:items-center"
            >
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Siap mulai perjalanan berikutnya?
                    </h2>
                    <p class="mt-2 max-w-xl text-sm text-zinc-300">
                        Cari penginapan di {{ stats.cities }} kota, pesan kamar,
                        dan atur pembayaran dalam satu tempat.
                    </p>
                </div>
                <Link
                    :href="propertiesIndex()"
                    class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-emerald-50"
                >
                    Cari penginapan
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>
    </section>
</template>
