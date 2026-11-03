<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, DoorOpen, Wrench } from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { Property, Room } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as bookingsIndex } from '@/routes/admin/bookings';
import { index as roomsIndex, status as roomStatus } from '@/routes/admin/rooms';

const props = defineProps<{
    property: Property;
    rooms: Room[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Kamar', href: bookingsIndex() },
        ],
    },
});

const statusForm = useForm({ status: 'available' });

const grouped = computed(() => props.property.room_types ?? []);

const summary = computed(() => ({
    available: props.rooms.filter((room) => room.status === 'available').length,
    maintenance: props.rooms.filter((room) => room.status === 'maintenance').length,
}));

function toggle(room: Room): void {
    statusForm.status = room.status === 'available' ? 'maintenance' : 'available';
    statusForm.patch(roomStatus(room.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Kamar ${property.name} — Panel Admin`" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="bookingsIndex()"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-emerald-600 dark:text-zinc-400"
            >
                <ArrowLeft class="size-4" />
                Semua booking
            </Link>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ property.city?.name }}</span>
        </div>

        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                Kamar {{ property.name }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Ubah status kamar untuk perawatan atau ketersediaan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-white/10 dark:bg-zinc-900">
            <span class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <DoorOpen class="size-4 text-emerald-600" />
                Tersedia: <strong>{{ summary.available }}</strong>
            </span>
            <span class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <Wrench class="size-4 text-rose-500" />
                Maintenance: <strong>{{ summary.maintenance }}</strong>
            </span>
            <Link
                :href="roomsIndex(property.slug)"
                class="ml-auto text-sm font-medium text-emerald-600 hover:underline"
            >
                Muat ulang
            </Link>
        </div>

        <div class="space-y-6">
            <section
                v-for="roomType in grouped"
                :key="roomType.id"
                class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200/80 px-5 py-3.5 dark:border-white/10">
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ roomType.name }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Kapasitas {{ roomType.capacity }} tamu · {{ roomType.rooms_count ?? 0 }} kamar
                        </p>
                    </div>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-white/5">
                    <div
                        v-for="room in rooms.filter((item) => item.room_type_id === roomType.id)"
                        :key="room.id"
                        class="flex flex-wrap items-center justify-between gap-3 px-5 py-3"
                    >
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ room.name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ room.active_bookings_count ?? 0 }} booking aktif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="room.status" />
                            <button
                                type="button"
                                :disabled="statusForm.processing"
                                class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-emerald-300 hover:text-emerald-700 disabled:opacity-60 dark:border-white/10 dark:text-zinc-200"
                                @click="toggle(room)"
                            >
                                {{ room.status === 'available' ? 'Set maintenance' : 'Set tersedia' }}
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="rooms.filter((item) => item.room_type_id === roomType.id).length === 0"
                        class="px-5 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Belum ada kamar pada tipe ini.
                    </p>
                </div>
            </section>

            <div
                v-if="grouped.length === 0"
                class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center text-sm text-zinc-500 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-400"
            >
                Properti ini belum memiliki tipe kamar.
            </div>
        </div>
    </div>
</template>
