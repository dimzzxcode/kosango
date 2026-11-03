<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BedDouble,
    Building2,
    CalendarDays,
    Check,
    CreditCard,
    LogIn,
    MapPin,
    ShieldCheck,
    Star,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PropertyCard from '@/components/PropertyCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import { gradientForSlug, softFor } from '@/lib/accents';
import {
    addDaysIso,
    formatDate,
    formatRupiah,
    periodLabel,
    periodUnit,
    todayIso,
} from '@/lib/format';
import type { Page, Property, RoomTypePrice } from '@/types';
import { store } from '@/routes/bookings';
import { availability } from '@/routes';
import { login, register } from '@/routes';
import { index as propertiesIndex } from '@/routes/properties';
import { show as showPage } from '@/routes/pages';

const props = defineProps<{
    property: Property;
    similarProperties: Property[];
    pages: Page[];
    priceRange: { min: number; max: number; unit: string } | null;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const rules = computed(() => page.props.bookingRules);

const roomTypes = computed(() => props.property.room_types ?? []);
const firstRoomType = roomTypes.value[0];

const activeRates = computed<RoomTypePrice[]>(() =>
    (firstRoomType?.prices ?? []).filter((price) => price.is_active),
);

const defaultPeriod = computed(
    () => activeRates.value[0]?.period ?? 'daily',
);

const form = useForm<{
    room_type_id: number;
    room_id: number;
    period: string;
    duration: number;
    payment_plan: 'full' | 'per_period' | 'installment';
    check_in: string;
    guests: number;
    phone: string;
    note: string;
    terms: boolean;
}>({
    room_type_id: firstRoomType?.id ?? 0,
    room_id: 0,
    period: defaultPeriod.value,
    duration: 1,
    payment_plan: 'full',
    check_in: addDaysIso(new Date(), 1),
    guests: Math.min(2, firstRoomType?.capacity ?? 2),
    phone: (user.value?.phone as string) ?? '',
    note: '',
    terms: false,
});

const selectedRoomType = computed(
    () =>
        roomTypes.value.find((roomType) => roomType.id === form.room_type_id) ??
        firstRoomType,
);

const availableRates = computed(() =>
    (selectedRoomType.value?.prices ?? []).filter((price) => price.is_active),
);

const selectedRate = computed(
    () => availableRates.value.find((price) => price.period === form.period) ?? null,
);

const allowedPlans = computed(() => {
    const plans: Array<{ value: 'full' | 'per_period' | 'installment'; title: string; description: string }> = [];

    if (form.period === 'yearly') {
        plans.push(
            {
                value: 'full',
                title: 'Lunas sekaligus',
                description: 'Satu tagihan untuk seluruh masa sewa.',
            },
            {
                value: 'installment',
                title: 'Cicilan',
                description: 'Pembayaran pertama minimal 50%, sisanya bebas.',
            },
        );
    } else {
        plans.push(
            {
                value: 'full',
                title: 'Lunas sekaligus',
                description: 'Satu tagihan untuk seluruh masa sewa.',
            },
            {
                value: 'per_period',
                title: 'Bayar per periode',
                description: 'Tagihan dibuat untuk setiap periode sewa.',
            },
        );
    }

    return plans;
});

type QuoteResponse = {
    check_in: string;
    check_out: string;
    duration: number;
    unit_price: number;
    total: number;
    unit: string;
    rooms: Array<{ id: number; name: string }>;
    message?: string;
    errors?: Record<string, string[]>;
};

const quote = ref<QuoteResponse | null>(null);
const quoteError = ref<string | null>(null);
const checking = ref(false);

let debounce: ReturnType<typeof setTimeout> | null = null;

async function fetchQuote(): Promise<void> {
    if (!form.room_type_id || !form.check_in) {
        return;
    }

    checking.value = true;
    quoteError.value = null;

    const params = new URLSearchParams({
        room_type_id: String(form.room_type_id),
        period: form.period,
        duration: String(form.duration),
        payment_plan: form.payment_plan,
        check_in: form.check_in,
    });

    try {
        const response = await fetch(`${availability().url}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        const payload = await response.json();

        if (!response.ok) {
            quote.value = null;
            quoteError.value =
                payload.message ??
                Object.values(payload.errors ?? {})
                    .flat()
                    .join(' ') ??
                'Tanggal ini tidak tersedia.';
            return;
        }

        quote.value = payload as QuoteResponse;

        const roomIds = quote.value.rooms.map((room) => room.id);

        if (!roomIds.includes(form.room_id)) {
            form.room_id = roomIds[0] ?? 0;
        }
    } catch {
        quote.value = null;
        quoteError.value = 'Gagal memeriksa ketersediaan. Coba lagi.';
    } finally {
        checking.value = false;
    }
}

function scheduleQuote(): void {
    if (debounce) {
        clearTimeout(debounce);
    }

    debounce = setTimeout(fetchQuote, 350);
}

watch(
    () => [form.room_type_id, form.period, form.duration, form.payment_plan, form.check_in],
    () => {
        if (selectedRate.value) {
            form.duration = Math.min(
                Math.max(form.duration, selectedRate.value.min_duration),
                selectedRate.value.max_duration,
            );
        }

        scheduleQuote();
    },
    { immediate: true },
);

watch(selectedRoomType, (roomType) => {
    if (!roomType) {
        return;
    }

    const rates = (roomType.prices ?? []).filter((price) => price.is_active);

    if (!rates.some((rate) => rate.period === form.period)) {
        form.period = rates[0]?.period ?? 'daily';
    }

    form.guests = Math.min(form.guests, roomType.capacity);
});

watch(
    () => form.period,
    () => {
        if (
            form.payment_plan !== 'full' &&
            !allowedPlans.value.some((plan) => plan.value === form.payment_plan)
        ) {
            form.payment_plan = 'full';
        }
    },
);

const typeLabel = computed(() =>
    props.property.type === 'kos' ? 'Kos' : 'Hotel',
);

const gallery = computed(() =>
    (props.property.images ?? []).filter((image) => image.url),
);

const termsPage = computed(() =>
    props.pages.find((page) => page.slug === 'syarat-ketentuan'),
);

const totalPrice = computed(() => quote.value?.total ?? 0);
const unitPrice = computed(() => quote.value?.unit_price ?? 0);

function selectRoomType(roomTypeId: number): void {
    form.room_type_id = roomTypeId;
}

function submit(): void {
    if (!user.value) {
        return;
    }

    form.post(store().url, {
        preserveScroll: true,
        onError: () => {
            document
                .getElementById('booking-form')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
    });
}

const jsonLd = computed(() => {
    const images = gallery.value.map((image) => image.url).filter(Boolean);

    return {
        '@context': 'https://schema.org',
        '@type': props.property.type === 'kos' ? 'LodgingBusiness' : 'Hotel',
        name: props.property.name,
        url: page.props.canonical,
        description: props.property.description,
        address: {
            '@type': 'PostalAddress',
            streetAddress: props.property.address,
            addressLocality: props.property.city?.name,
            addressRegion: props.property.city?.province,
            addressCountry: 'ID',
        },
        ...(images.length > 0 ? { image: images } : {}),
        ...(props.property.rating_avg > 0
            ? {
                  aggregateRating: {
                      '@type': 'AggregateRating',
                      ratingValue: props.property.rating_avg,
                      reviewCount: props.property.reviews_count,
                  },
              }
            : {}),
        ...(props.priceRange
            ? {
                  priceRange: `${formatRupiah(props.priceRange.min)} - ${formatRupiah(props.priceRange.max)}`,
              }
            : {}),
    };
});
</script>

<template>
    <SeoHead
        :title="property.name"
        :description="
            property.description ??
            `Sewa ${typeLabel.toLowerCase()} ${property.name} di ${property.city?.name}.`
        "
        :image="property.thumbnail_url"
        :json-ld="jsonLd"
    />

    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
        <Link
            :href="propertiesIndex()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-emerald-600 dark:text-zinc-400"
        >
            <ArrowLeft class="size-4" />
            Kembali ke pencarian
        </Link>

        <div
            class="relative mt-5 overflow-hidden rounded-3xl p-6 text-white sm:p-10"
            :class="property.thumbnail_url ? '' : `bg-gradient-to-br ${gradientForSlug(property.slug)}`"
        >
            <img
                v-if="property.thumbnail_url"
                :src="property.thumbnail_url"
                :alt="property.thumbnail_alt ?? property.name"
                class="absolute inset-0 size-full object-cover"
            />
            <div
                v-if="property.thumbnail_url"
                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10"
            />
            <div
                v-else
                class="absolute -top-24 -right-16 size-72 rounded-full border border-white/20 bg-white/10"
            />
            <div class="relative">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full border border-white/25 bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        {{ typeLabel }}
                    </span>
                    <span
                        class="flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        <Star class="size-3.5 fill-amber-300 text-amber-300" />
                        {{ Number(property.rating_avg).toFixed(1) }} ·
                        {{ property.reviews_count }} ulasan
                    </span>
                    <span
                        class="flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        <MapPin class="size-3.5" />
                        {{ property.city?.name }}
                    </span>
                </div>

                <h1 class="mt-6 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ property.name }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-white/80">
                    {{ property.address }}, {{ property.city?.province }}
                </p>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_400px] lg:items-start">
            <div class="space-y-8">
                <section>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                        Tentang penginapan
                    </h2>
                    <p class="mt-3 leading-relaxed text-zinc-600 dark:text-zinc-300">
                        {{ property.description }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span
                            v-for="facility in property.facilities ?? []"
                            :key="facility.id"
                            class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:bg-white/10 dark:text-zinc-200"
                        >
                            <Check class="size-3.5 text-emerald-600" />
                            {{ facility.name }}
                        </span>
                    </div>
                </section>

                <section v-if="gallery.length > 0">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                        Galeri
                    </h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="image in gallery"
                            :key="image.id"
                            class="overflow-hidden rounded-2xl border border-zinc-200/80 dark:border-white/10"
                        >
                            <img
                                :src="image.url ?? ''"
                                :alt="image.alt ?? property.name"
                                loading="lazy"
                                class="h-40 w-full object-cover transition duration-500 hover:scale-105"
                            />
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                                Tipe kamar & tarif
                            </h2>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Tarif ditetapkan per tipe kamar untuk setiap
                                periode sewa.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        <article
                            v-for="roomType in roomTypes"
                            :key="roomType.id"
                            class="rounded-2xl border bg-white p-5 transition dark:bg-zinc-900"
                            :class="
                                roomType.id === form.room_type_id
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/15'
                                    : 'border-zinc-200/80 hover:border-emerald-300 dark:border-white/10'
                            "
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-zinc-900 dark:text-white">
                                            {{ roomType.name }}
                                        </h3>
                                        <span
                                            v-if="roomType.id === form.room_type_id"
                                            class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                        >
                                            Dipilih
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                        {{ roomType.description }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="flex items-center gap-1.5">
                                            <Users class="size-3.5" />
                                            Maks. {{ roomType.capacity }} tamu
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <BedDouble class="size-3.5" />
                                            {{ roomType.rooms_count ?? roomType.rooms?.length ?? 0 }} kamar
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="rate in (roomType.prices ?? []).filter((price) => price.is_active)"
                                            :key="rate.id"
                                            class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-600 dark:bg-white/5 dark:text-zinc-300"
                                        >
                                            {{ formatRupiah(rate.price) }} /
                                            {{ periodUnit(rate.period) }}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                    :class="
                                        roomType.id === form.room_type_id
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200'
                                    "
                                    @click="selectRoomType(roomType.id)"
                                >
                                    Pilih tipe
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

                <section
                    class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5 dark:border-emerald-500/20 dark:bg-emerald-500/5"
                >
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm dark:bg-zinc-900">
                            <ShieldCheck class="size-5" />
                        </span>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">
                                Pembayaran fleksibel & refund transparan
                            </p>
                            <p class="mt-1 max-w-xl text-sm text-zinc-600 dark:text-zinc-300">
                                Lunas, per periode, atau cicilan tahunan. Denda
                                {{ formatRupiah(rules.lateFee) }} dikenakan sekali
                                setelah toleransi {{ rules.lateGraceDays }} hari.
                                Refund penuh pokok sewa dalam
                                {{ rules.refundWindowDays }} hari setelah
                                konfirmasi bila belum check-in.
                            </p>
                        </div>
                    </div>
                </section>

                <section v-if="property.reviews && property.reviews.length > 0">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                        Ulasan tamu
                    </h2>
                    <div class="mt-4 space-y-3">
                        <article
                            v-for="review in property.reviews"
                            :key="review.id"
                            class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ review.user?.name ?? 'Tamu KosanGo' }}
                                </p>
                                <span class="flex items-center gap-0.5">
                                    <Star
                                        v-for="index in 5"
                                        :key="index"
                                        class="size-3.5"
                                        :class="
                                            index <= review.rating
                                                ? 'fill-amber-400 text-amber-400'
                                                : 'text-zinc-300 dark:text-zinc-600'
                                        "
                                    />
                                </span>
                            </div>
                            <p
                                v-if="review.comment"
                                class="mt-2 text-sm text-zinc-600 dark:text-zinc-300"
                            >
                                {{ review.comment }}
                            </p>
                            <p class="mt-2 text-xs text-zinc-400">
                                {{ formatDate(review.created_at) }}
                            </p>
                        </article>
                    </div>
                </section>
            </div>

            <aside id="booking-form" class="lg:sticky lg:top-24">
                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xl shadow-zinc-900/5 dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Pesan kamar
                        </h2>
                        <span
                            v-if="selectedRoomType"
                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="softFor(property.accent)"
                        >
                            {{ selectedRoomType.name }}
                        </span>
                    </div>

                    <div
                        v-if="!user"
                        class="mt-5 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            Masuk untuk memesan
                        </p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Kamu perlu akun KosanGo untuk membuat booking,
                            mengunggah bukti transfer, dan mengelola refund.
                        </p>
                        <div class="mt-4 flex gap-2">
                            <Link
                                :href="login()"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500"
                            >
                                <LogIn class="size-4" />
                                Masuk
                            </Link>
                            <Link
                                :href="register()"
                                class="inline-flex flex-1 items-center justify-center rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-white/15 dark:text-zinc-200 dark:hover:bg-white/10"
                            >
                                Daftar
                            </Link>
                        </div>
                    </div>

                    <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                Tanggal mulai
                            </span>
                            <input
                                v-model="form.check_in"
                                type="date"
                                :min="todayIso()"
                                class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                            />
                            <InputError class="mt-1" :message="form.errors.check_in" />
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                    Periode
                                </span>
                                <select
                                    v-model="form.period"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                                >
                                    <option
                                        v-for="rate in availableRates"
                                        :key="rate.period"
                                        :value="rate.period"
                                    >
                                        {{ periodLabel(rate.period) }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.period" />
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                    Durasi ({{ periodUnit(form.period) }})
                                </span>
                                <input
                                    v-model.number="form.duration"
                                    type="number"
                                    :min="selectedRate?.min_duration ?? 1"
                                    :max="selectedRate?.max_duration ?? 30"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                                />
                                <InputError class="mt-1" :message="form.errors.duration" />
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                    Jumlah tamu
                                </span>
                                <input
                                    v-model.number="form.guests"
                                    type="number"
                                    min="1"
                                    :max="selectedRoomType?.capacity ?? 2"
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                                />
                                <InputError class="mt-1" :message="form.errors.guests" />
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                    No. WhatsApp
                                </span>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="0812..."
                                    class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                                />
                                <InputError class="mt-1" :message="form.errors.phone" />
                            </label>
                        </div>

                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                Kamar tersedia
                            </span>
                            <select
                                v-model.number="form.room_id"
                                class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                            >
                                <option :value="0" disabled>
                                    {{ checking ? 'Memeriksa kamar...' : 'Pilih kamar' }}
                                </option>
                                <option
                                    v-for="room in quote?.rooms ?? []"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    {{ room.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.room_id" />
                        </label>

                        <div>
                            <span class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                Cara pembayaran
                            </span>
                            <div class="space-y-2">
                                <label
                                    v-for="plan in allowedPlans"
                                    :key="plan.value"
                                    class="flex cursor-pointer items-start gap-2.5 rounded-lg border p-2.5 transition"
                                    :class="
                                        form.payment_plan === plan.value
                                            ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-500/10'
                                            : 'border-zinc-200 hover:border-zinc-300 dark:border-white/10'
                                    "
                                >
                                    <input
                                        v-model="form.payment_plan"
                                        type="radio"
                                        :value="plan.value"
                                        class="mt-0.5 accent-emerald-600"
                                    />
                                    <span>
                                        <span class="block text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ plan.title }}
                                        </span>
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ plan.description }}
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <InputError class="mt-1" :message="form.errors.payment_plan" />
                        </div>

                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                Catatan (opsional)
                            </span>
                            <textarea
                                v-model="form.note"
                                rows="2"
                                placeholder="Permintaan khusus, jam tiba, dll."
                                class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 dark:border-white/10 dark:bg-white/5"
                            />
                            <InputError class="mt-1" :message="form.errors.note" />
                        </label>

                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-white/5">
                            <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <CalendarDays class="size-3.5" />
                                <template v-if="quote">
                                    {{ formatDate(quote.check_in) }} –
                                    {{ formatDate(quote.check_out) }}
                                    · {{ quote.duration }}
                                    {{ periodUnit(form.period) }}
                                </template>
                                <template v-else>
                                    Pilih tanggal untuk melihat jadwal
                                </template>
                            </div>

                            <div
                                v-if="quoteError"
                                class="mt-3 flex items-start gap-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                            >
                                {{ quoteError }}
                            </div>

                            <div v-else-if="quote" class="mt-3 space-y-1.5 text-sm">
                                <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                    <span>
                                        {{ formatRupiah(unitPrice) }} ×
                                        {{ quote.duration }}
                                        {{ periodUnit(form.period) }}
                                    </span>
                                    <span>{{ formatRupiah(totalPrice) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold text-zinc-900 dark:border-white/10 dark:text-white">
                                    <span>Total</span>
                                    <span>{{ formatRupiah(totalPrice) }}</span>
                                </div>
                            </div>

                            <div
                                v-if="form.payment_plan === 'installment'"
                                class="mt-3 flex items-start gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"
                            >
                                <Wallet class="mt-0.5 size-3.5 shrink-0" />
                                <span>
                                    Pembayaran pertama minimal
                                    <strong>
                                        {{
                                            formatRupiah(
                                                Math.ceil(
                                                    (totalPrice *
                                                        rules.minDownPaymentPercent) /
                                                        100,
                                                ),
                                            )
                                        }}
                                    </strong>
                                    dari total, sisanya bebas sampai lunas
                                    sebelum check-out.
                                </span>
                            </div>
                        </div>

                        <label class="flex items-start gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                class="mt-0.5 accent-emerald-600"
                            />
                            <span>
                                Saya menyetujui
                                <Link
                                    v-if="termsPage"
                                    :href="showPage(termsPage.slug)"
                                    class="font-medium text-emerald-600 underline-offset-4 hover:underline dark:text-emerald-400"
                                >
                                    syarat dan ketentuan
                                </Link>
                                <template v-else>syarat dan ketentuan</template>
                                KosanGo versi {{ rules.termsVersion }}.
                            </span>
                        </label>
                        <InputError :message="form.errors.terms" />
                        <InputError :message="page.props.errors.booking" />

                        <button
                            type="submit"
                            :disabled="
                                form.processing ||
                                !quote ||
                                !form.room_id ||
                                !!quoteError
                            "
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-60"
                        >
                            <CreditCard class="size-4" />
                            {{ form.processing ? 'Memproses...' : 'Buat Booking' }}
                        </button>

                        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                            Harga, tanggal selesai, dan jadwal tagihan dihitung
                            oleh server.
                        </p>
                    </form>
                </div>
            </aside>
        </div>

        <section
            v-if="similarProperties.length > 0"
            class="mt-14 border-t border-zinc-200/70 pt-10 dark:border-white/10"
        >
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                    Penginapan lain di {{ property.city?.name }}
                </h2>
                <Building2 class="size-5 text-zinc-400" />
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <PropertyCard
                    v-for="similar in similarProperties"
                    :key="similar.id"
                    :property="similar"
                />
            </div>
        </section>
    </div>
</template>
