<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, MapPin, Star } from '@lucide/vue';
import { computed } from 'vue';
import { gradientFor } from '@/lib/accents';
import { formatRupiah, startingPrice } from '@/lib/format';
import type { Property } from '@/types';
import { show } from '@/routes/properties';

const props = defineProps<{
    property: Property;
    period?: string;
}>();

const price = computed(() => startingPrice(props.property, props.period));

const typeLabel = computed(() =>
    props.property.type === 'kos' ? 'Kos' : 'Hotel',
);

const rating = computed(() => Number(props.property.rating_avg ?? 0).toFixed(1));
</script>

<template>
    <Link
        :href="show(property.slug)"
        class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-white/10 dark:bg-zinc-900"
    >
        <div class="relative h-44 overflow-hidden">
            <img
                v-if="property.thumbnail_url"
                :src="property.thumbnail_url"
                :alt="property.thumbnail_alt ?? property.name"
                loading="lazy"
                class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105"
            />
            <div
                v-else
                class="absolute inset-0 bg-gradient-to-br"
                :class="gradientFor(property.accent)"
            />

            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />

            <div class="relative flex h-full flex-col justify-between p-5 text-white">
                <div class="flex items-start justify-between gap-2">
                    <span
                        class="rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        {{ typeLabel }}
                    </span>
                    <span
                        class="flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium backdrop-blur-sm"
                    >
                        <Star class="size-3.5 fill-amber-300 text-amber-300" />
                        {{ rating }}
                        <span class="text-white/70">
                            ({{ property.reviews_count }})
                        </span>
                    </span>
                </div>

                <div class="mt-auto">
                    <p class="text-xs font-medium text-white/80">
                        {{ property.city?.name }}
                    </p>
                    <h3 class="mt-1 line-clamp-1 text-lg leading-snug font-semibold">
                        {{ property.name }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-5">
            <p
                class="flex items-start gap-1.5 text-sm text-zinc-500 dark:text-zinc-400"
            >
                <MapPin class="mt-0.5 size-4 shrink-0" />
                <span class="line-clamp-1">{{ property.address }}</span>
            </p>

            <div class="mt-auto flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Mulai dari
                    </p>
                    <p class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ price ? formatRupiah(price.price) : '-' }}
                        <span class="text-xs font-normal text-zinc-500">
                            / {{ price?.unit ?? 'periode' }}
                        </span>
                    </p>
                </div>
                <span
                    class="flex size-9 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 transition group-hover:bg-emerald-500 group-hover:text-white dark:bg-white/10 dark:text-white"
                >
                    <ArrowUpRight class="size-4" />
                </span>
            </div>
        </div>
    </Link>
</template>
