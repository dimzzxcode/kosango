<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Check, DoorOpen, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { periodLabel, periodUnit } from '@/lib/format';
import type {
    BookingPeriod,
    Room,
    RoomStatus,
    RoomType,
} from '@/types';
import {
    destroy as destroyRoomType,
    update as updateRoomType,
} from '@/routes/superadmin/room-types';
import { update as updateRoomPrices } from '@/routes/superadmin/room-types/prices';
import {
    destroy as destroyRoom,
    store as storeRoom,
    update as updateRoom,
} from '@/routes/superadmin/rooms';

const props = defineProps<{
    propertyId: number;
    roomType: RoomType;
}>();

const page = usePage();

type PriceRow = {
    period: BookingPeriod;
    price: number;
    min_duration: number;
    max_duration: number;
    is_active: boolean;
};

const defaultMaxDuration: Record<BookingPeriod, number> = {
    daily: 30,
    monthly: 24,
    yearly: 5,
};

function buildPriceRows(roomType: RoomType): PriceRow[] {
    const periods: BookingPeriod[] = ['daily', 'monthly', 'yearly'];

    return periods.map((period) => {
        const rate = roomType.prices?.find((item) => item.period === period);

        return {
            period,
            price: rate?.price ?? 0,
            min_duration: rate?.min_duration ?? 1,
            max_duration: rate?.max_duration ?? defaultMaxDuration[period],
            is_active: rate?.is_active ?? false,
        };
    });
}

const detailsOpen = ref(false);
const addingRoom = ref(false);
const editingRoom = ref<Room | null>(null);

const detailsForm = useForm({
    name: props.roomType.name,
    description: props.roomType.description ?? '',
    capacity: props.roomType.capacity,
});

const pricesForm = useForm<{ prices: PriceRow[] }>({
    prices: buildPriceRows(props.roomType),
});

const roomsForm = useForm<{
    property_id: number;
    room_type_id: number;
    name: string;
    status: RoomStatus;
}>({
    property_id: props.propertyId,
    room_type_id: props.roomType.id,
    name: '',
    status: 'available',
});

const deleteTypeForm = useForm({});
const deleteRoomForm = useForm({});

function toggleDetails(): void {
    detailsOpen.value = !detailsOpen.value;
    detailsForm.name = props.roomType.name;
    detailsForm.description = props.roomType.description ?? '';
    detailsForm.capacity = props.roomType.capacity;
}

function submitDetails(): void {
    detailsForm.put(updateRoomType(props.roomType.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            detailsOpen.value = false;
        },
    });
}

function submitPrices(): void {
    const rows = pricesForm.prices.filter(
        (row) => row.price > 0 || row.is_active,
    );

    if (rows.length === 0) {
        pricesForm.setError('prices', 'Isi minimal satu tarif periode.');

        return;
    }

    pricesForm
        .transform((data) => ({
            prices: data.prices.filter((row) => row.price > 0 || row.is_active),
        }))
        .put(updateRoomPrices(props.roomType.id).url, {
            preserveScroll: true,
        });
}

function priceError(index: number, field: string): string | undefined {
    const errors = pricesForm.errors as Record<string, string | undefined>;

    return errors[`prices.${index}.${field}`];
}

function startAddRoom(): void {
    editingRoom.value = null;
    addingRoom.value = true;
    roomsForm.reset();
    roomsForm.status = 'available';
}

function startEditRoom(room: Room): void {
    addingRoom.value = false;
    editingRoom.value = room;
    roomsForm.name = room.name;
    roomsForm.status = room.status;
    roomsForm.room_type_id = room.room_type_id;
}

function cancelRoom(): void {
    addingRoom.value = false;
    editingRoom.value = null;
    roomsForm.reset();
    roomsForm.status = 'available';
}

function submitRoom(): void {
    if (editingRoom.value) {
        roomsForm.put(updateRoom(editingRoom.value.id).url, {
            preserveScroll: true,
            onSuccess: () => cancelRoom(),
        });

        return;
    }

    roomsForm.post(storeRoom().url, {
        preserveScroll: true,
        onSuccess: () => {
            addingRoom.value = false;
            roomsForm.reset();
            roomsForm.status = 'available';
        },
    });
}

function removeType(): void {
    deleteTypeForm.delete(destroyRoomType(props.roomType.id).url, {
        preserveScroll: true,
    });
}

function removeRoom(room: Room): void {
    deleteRoomForm.delete(destroyRoom(room.id).url, { preserveScroll: true });
}

function activeRateCount(): number {
    return (props.roomType.prices ?? []).filter((rate) => rate.is_active).length;
}
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200/80 px-5 py-3.5 dark:border-white/10">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">
                        {{ roomType.name }}
                    </h3>
                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                        Kapasitas {{ roomType.capacity }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ activeRateCount() }} tarif aktif · {{ (roomType.rooms ?? []).length }} kamar
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200"
                    @click="toggleDetails"
                >
                    <Pencil class="size-3.5" />
                    {{ detailsOpen ? 'Tutup' : 'Ubah tipe' }}
                </button>
                <button
                    type="button"
                    :disabled="deleteTypeForm.processing"
                    class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-60 dark:border-rose-500/20 dark:text-rose-400"
                    @click="removeType"
                >
                    <Trash2 class="size-3.5" />
                    Hapus tipe
                </button>
            </div>
        </div>

        <p
            v-if="page.props.errors.room_type"
            class="mx-5 mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
        >
            {{ page.props.errors.room_type }}
        </p>

        <form
            v-if="detailsOpen"
            class="grid gap-3 border-b border-zinc-200/80 bg-zinc-50/60 px-5 py-4 sm:grid-cols-2 dark:border-white/10 dark:bg-white/5"
            @submit.prevent="submitDetails"
        >
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama tipe</span>
                <input
                    v-model="detailsForm.name"
                    type="text"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                />
                <InputError class="mt-1" :message="detailsForm.errors.name" />
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Kapasitas</span>
                <input
                    v-model.number="detailsForm.capacity"
                    type="number"
                    min="1"
                    max="20"
                    class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                />
                <InputError class="mt-1" :message="detailsForm.errors.capacity" />
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Deskripsi</span>
                <textarea
                    v-model="detailsForm.description"
                    rows="2"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-zinc-900"
                />
                <InputError class="mt-1" :message="detailsForm.errors.description" />
            </label>
            <div class="flex items-center gap-2 sm:col-span-2">
                <button
                    type="submit"
                    :disabled="detailsForm.processing"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Check class="size-4" />
                    Simpan tipe
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center rounded-lg border border-zinc-200 px-3 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                    @click="toggleDetails"
                >
                    Batal
                </button>
            </div>
        </form>

        <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Tarif per periode</h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Harga minimal Rp 1.000. Centang periode yang ditawarkan.
                </p>
            </div>

            <div class="mt-3 space-y-2">
                <div
                    v-for="(row, index) in pricesForm.prices"
                    :key="row.period"
                    class="rounded-xl border border-zinc-200/80 p-3 dark:border-white/10"
                >
                    <div class="grid gap-2 sm:grid-cols-[1.1fr_1.3fr_0.8fr_0.8fr_auto] sm:items-end">
                        <div>
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Periode</span>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ periodLabel(row.period) }}
                            </p>
                        </div>
                        <label class="block">
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                Harga / {{ periodUnit(row.period) }}
                            </span>
                            <input
                                v-model.number="row.price"
                                type="number"
                                min="1000"
                                step="1000"
                                class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                            />
                            <InputError class="mt-1" :message="priceError(index, 'price')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Min</span>
                            <input
                                v-model.number="row.min_duration"
                                type="number"
                                min="1"
                                max="60"
                                class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                            />
                            <InputError class="mt-1" :message="priceError(index, 'min_duration')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Maks</span>
                            <input
                                v-model.number="row.max_duration"
                                type="number"
                                min="1"
                                max="60"
                                class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                            />
                            <InputError class="mt-1" :message="priceError(index, 'max_duration')" />
                        </label>
                        <label class="flex h-9 items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <input v-model="row.is_active" type="checkbox" class="accent-emerald-600" />
                            Aktif
                        </label>
                    </div>
                </div>
            </div>

            <InputError class="mt-2" :message="pricesForm.errors.prices" />

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Tarif aktif:
                    {{
                        pricesForm.prices
                            .filter((row) => row.is_active && row.price > 0)
                            .map((row) => periodLabel(row.period))
                            .join(', ') || 'belum ada'
                    }}
                </p>
                <button
                    type="button"
                    :disabled="pricesForm.processing"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                    @click="submitPrices"
                >
                    <Check class="size-4" />
                    Simpan tarif
                </button>
            </div>
        </div>

        <div class="px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Kamar</h4>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200"
                    @click="startAddRoom"
                >
                    <Plus class="size-3.5" />
                    Tambah kamar
                </button>
            </div>

            <p
                v-if="page.props.errors.room"
                class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
            >
                {{ page.props.errors.room }}
            </p>

            <ul class="mt-3 divide-y divide-zinc-100 dark:divide-white/5">
                <li
                    v-for="room in roomType.rooms ?? []"
                    :key="room.id"
                    class="flex flex-wrap items-center justify-between gap-2 py-2.5"
                >
                    <div class="flex items-center gap-2">
                        <DoorOpen class="size-4 text-emerald-600" />
                        <span class="text-sm text-zinc-800 dark:text-zinc-100">{{ room.name }}</span>
                        <StatusBadge :status="room.status" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200"
                            @click="startEditRoom(room)"
                        >
                            Ubah
                        </button>
                        <button
                            type="button"
                            :disabled="deleteRoomForm.processing"
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-60 dark:border-rose-500/20 dark:text-rose-400"
                            @click="removeRoom(room)"
                        >
                            <Trash2 class="size-3" />
                            Hapus
                        </button>
                    </div>
                </li>
            </ul>

            <p
                v-if="(roomType.rooms ?? []).length === 0 && !addingRoom"
                class="mt-3 rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-white/15 dark:text-zinc-400"
            >
                Belum ada kamar pada tipe ini. Properti butuh minimal satu kamar untuk dipublikasikan.
            </p>

            <form
                v-if="addingRoom || editingRoom"
                class="mt-3 grid gap-2 rounded-xl bg-zinc-50 p-3 sm:grid-cols-[1.4fr_1fr_auto_auto] sm:items-end dark:bg-white/5"
                @submit.prevent="submitRoom"
            >
                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama kamar</span>
                    <input
                        v-model="roomsForm.name"
                        type="text"
                        class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                    />
                    <InputError class="mt-1" :message="roomsForm.errors.name" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Status</span>
                    <select
                        v-model="roomsForm.status"
                        class="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                    >
                        <option value="available">Tersedia</option>
                        <option value="maintenance">Perbaikan</option>
                    </select>
                    <InputError class="mt-1" :message="roomsForm.errors.status" />
                </label>
                <button
                    type="submit"
                    :disabled="roomsForm.processing"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Check class="size-4" />
                    {{ editingRoom ? 'Simpan' : 'Tambah' }}
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 items-center rounded-lg border border-zinc-200 px-3 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                    @click="cancelRoom"
                >
                    <X class="size-4" />
                </button>
                <InputError class="sm:col-span-4" :message="roomsForm.errors.room_type_id" />
            </form>
        </div>
    </div>
</template>
