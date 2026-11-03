<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    Check,
    ChevronDown,
    ChevronUp,
    Eye,
    EyeOff,
    ImagePlus,
    MapPin,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import RoomTypeEditor from '@/components/RoomTypeEditor.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type {
    City,
    Facility,
    Property,
    PropertyImage,
    PropertyType,
} from '@/types';
import { dashboard } from '@/routes/admin';
import {
    admins as syncAdmins,
    index as propertiesIndex,
    publish,
    store,
    update,
} from '@/routes/superadmin/properties';
import {
    destroy as destroyPropertyImage,
    order as reorderPropertyImages,
} from '@/routes/superadmin/properties/images';
import { store as storeRoomType } from '@/routes/superadmin/room-types';

type PropertyTypeOption = {
    value: PropertyType;
    label: string;
};

type AdminOption = {
    id: number;
    name: string;
    email: string;
};

const props = defineProps<{
    property: Property | null;
    cities: Array<Pick<City, 'id' | 'name' | 'province'>>;
    facilities: Array<Pick<Facility, 'id' | 'name'>>;
    types: PropertyTypeOption[];
    admins?: AdminOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Properti', href: propertiesIndex() },
        ],
    },
});

const page = usePage();
const maxGalleryImages = computed(
    () => page.props.bookingRules.maxGalleryImages,
);

const form = useForm<{
    city_id: number | null;
    type: PropertyType;
    name: string;
    description: string;
    address: string;
    latitude: string;
    longitude: string;
    whatsapp_number: string;
    facilities: number[];
    thumbnail_alt: string;
    thumbnail: File | null;
    gallery: File[];
}>({
    city_id: props.property?.city_id ?? null,
    type: props.property?.type ?? 'kos',
    name: props.property?.name ?? '',
    description: props.property?.description ?? '',
    address: props.property?.address ?? '',
    latitude: props.property?.latitude?.toString() ?? '',
    longitude: props.property?.longitude?.toString() ?? '',
    whatsapp_number: props.property?.whatsapp_number ?? '',
    facilities: props.property?.facilities?.map((facility) => facility.id) ?? [],
    thumbnail_alt: props.property?.thumbnail_alt ?? '',
    thumbnail: null,
    gallery: [],
});

const publishForm = useForm({});
const imageForm = useForm({});
const adminsForm = useForm<{ admin_ids: number[] }>({
    admin_ids: props.property?.admins?.map((admin) => admin.id) ?? [],
});
const roomTypeForm = useForm({
    property_id: props.property?.id ?? 0,
    name: '',
    description: '',
    capacity: 2,
});

const showRoomTypeForm = ref(false);
const galleryInput = ref<HTMLInputElement | null>(null);

function pickThumbnail(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.thumbnail = target.files?.[0] ?? null;
}

function pickGallery(event: Event): void {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files ?? []);
    const existing = props.property?.images?.length ?? 0;
    const remaining = Math.max(0, maxGalleryImages.value - existing);

    form.gallery = files.slice(0, remaining);
}

function clearGallery(): void {
    form.gallery = [];

    if (galleryInput.value) {
        galleryInput.value.value = '';
    }
}

const reordering = ref(false);

function moveImage(index: number, direction: -1 | 1): void {
    if (!props.property) {
        return;
    }

    const images = [...(props.property.images ?? [])];
    const target = index + direction;

    if (target < 0 || target >= images.length) {
        return;
    }

    [images[index], images[target]] = [images[target], images[index]];

    reordering.value = true;

    router.put(
        reorderPropertyImages(props.property.slug).url,
        { ids: images.map((image) => image.id) },
        {
            preserveScroll: true,
            onFinish: () => {
                reordering.value = false;
            },
        },
    );
}

function submit(): void {
    if (props.property) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            update(props.property.slug).url,
            {
                forceFormData: true,
                preserveScroll: true,
            },
        );

        return;
    }

    form.post(store().url, { forceFormData: true });
}

function togglePublish(): void {
    if (!props.property) {
        return;
    }

    publishForm.patch(publish(props.property.slug).url, {
        preserveScroll: true,
    });
}

function removeImage(image: PropertyImage): void {
    if (!props.property) {
        return;
    }

    imageForm.delete(
        destroyPropertyImage({
            property: props.property.slug,
            image: image.id,
        }).url,
        { preserveScroll: true },
    );
}

function submitAdmins(): void {
    if (!props.property) {
        return;
    }

    adminsForm.put(syncAdmins(props.property.slug).url, {
        preserveScroll: true,
    });
}

function toggleRoomTypeForm(): void {
    showRoomTypeForm.value = !showRoomTypeForm.value;
    roomTypeForm.reset();
    roomTypeForm.property_id = props.property?.id ?? 0;
}

function submitRoomType(): void {
    if (!props.property) {
        return;
    }

    roomTypeForm.post(storeRoomType(props.property.slug).url, {
        preserveScroll: true,
        onSuccess: () => {
            roomTypeForm.reset();
            roomTypeForm.property_id = props.property?.id ?? 0;
            showRoomTypeForm.value = false;
        },
    });
}
</script>

<template>
    <Head :title="property ? `Kelola ${property.name} — Superadmin` : 'Properti Baru — Superadmin'" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="propertiesIndex().url"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-emerald-600 dark:text-zinc-400"
            >
                <ArrowLeft class="size-4" />
                Semua properti
            </Link>

            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge v-if="property" :status="property.status" />
                <button
                    v-if="property"
                    type="button"
                    :disabled="publishForm.processing"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition disabled:opacity-60"
                    :class="
                        property.status === 'published'
                            ? 'border-zinc-200 text-zinc-700 hover:border-amber-300 hover:text-amber-700 dark:border-white/10 dark:text-zinc-200'
                            : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10'
                    "
                    @click="togglePublish"
                >
                    <component
                        :is="property.status === 'published' ? EyeOff : Eye"
                        class="size-3.5"
                    />
                    {{ property.status === 'published' ? 'Jadikan draft' : 'Publikasikan' }}
                </button>
            </div>
        </div>

        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                {{ property ? property.name : 'Properti baru' }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{
                    property
                        ? 'Perbarui informasi properti, galeri, admin, tipe kamar, tarif, dan kamar.'
                        : 'Properti dibuat sebagai draft. Lengkapi tipe kamar dan kamar sebelum publikasi.'
                }}
            </p>
        </div>

        <p
            v-if="page.props.errors.property"
            class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
        >
            {{ page.props.errors.property }}
        </p>

        <form
            class="space-y-5 rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
            @submit.prevent="submit"
        >
            <div class="flex items-center gap-2">
                <Building2 class="size-4 text-emerald-600" />
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Informasi properti</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama properti</span>
                    <input
                        v-model="form.name"
                        type="text"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.name" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Jenis</span>
                    <select
                        v-model="form.type"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <option v-for="type in types" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.type" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Kota</span>
                    <select
                        v-model="form.city_id"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    >
                        <option :value="null">Pilih kota</option>
                        <option v-for="city in cities" :key="city.id" :value="city.id">
                            {{ city.name }} · {{ city.province }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.city_id" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nomor WhatsApp</span>
                    <input
                        v-model="form.whatsapp_number"
                        type="text"
                        placeholder="6281234567890"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.whatsapp_number" />
                </label>

                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Alamat lengkap</span>
                    <input
                        v-model="form.address"
                        type="text"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.address" />
                </label>

                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Deskripsi</span>
                    <textarea
                        v-model="form.description"
                        rows="5"
                        class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.description" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Latitude (opsional)</span>
                    <input
                        v-model="form.latitude"
                        type="text"
                        placeholder="-6.200000"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.latitude" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Longitude (opsional)</span>
                    <input
                        v-model="form.longitude"
                        type="text"
                        placeholder="106.816666"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.longitude" />
                </label>
            </div>

            <div>
                <span class="mb-2 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                    <MapPin class="size-3.5" />
                    Fasilitas
                </span>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <label
                        v-for="facility in facilities"
                        :key="facility.id"
                        class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-white/10 dark:text-zinc-200"
                    >
                        <input
                            v-model="form.facilities"
                            type="checkbox"
                            :value="facility.id"
                            class="accent-emerald-600"
                        />
                        {{ facility.name }}
                    </label>
                </div>
                <p v-if="facilities.length === 0" class="text-xs text-zinc-500 dark:text-zinc-400">
                    Belum ada data fasilitas. Tambahkan lebih dulu di menu Fasilitas.
                </p>
                <InputError class="mt-1" :message="form.errors.facilities" />
            </div>

            <div class="grid gap-4 border-t border-zinc-100 pt-4 sm:grid-cols-2 dark:border-white/5">
                <div>
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                        Thumbnail {{ property ? '(biarkan kosong jika tidak diubah)' : '' }}
                    </span>
                    <div class="flex items-center gap-3">
                        <div class="h-20 w-28 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5">
                            <img
                                v-if="property?.thumbnail_url"
                                :src="property.thumbnail_url"
                                :alt="property.thumbnail_alt ?? property.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-zinc-400"
                            >
                                <ImagePlus class="size-5" />
                            </div>
                        </div>
                        <label
                            class="flex cursor-pointer flex-1 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-3 py-4 text-center transition hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-white/15 dark:bg-white/5 dark:hover:border-emerald-500/60"
                        >
                            <ImagePlus class="size-4 text-emerald-600" />
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">
                                {{ form.thumbnail ? form.thumbnail.name : 'Pilih thumbnail' }}
                            </span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                JPG, PNG, WEBP · maks 2 MB
                            </span>
                            <input
                                type="file"
                                class="sr-only"
                                accept=".jpg,.jpeg,.png,.webp"
                                @change="pickThumbnail"
                            />
                        </label>
                    </div>
                    <InputError class="mt-1" :message="form.errors.thumbnail" />
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                        Alt text thumbnail
                    </span>
                    <input
                        v-model="form.thumbnail_alt"
                        type="text"
                        class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5"
                    />
                    <InputError class="mt-1" :message="form.errors.thumbnail_alt" />
                </label>
            </div>

            <div class="border-t border-zinc-100 pt-4 dark:border-white/5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        Galeri · maksimal {{ maxGalleryImages }} gambar
                    </span>
                    <button
                        v-if="form.gallery.length > 0"
                        type="button"
                        class="text-xs text-zinc-500 hover:text-rose-600 dark:text-zinc-400"
                        @click="clearGallery"
                    >
                        Kosongkan pilihan
                    </button>
                </div>

                <label
                    class="mt-2 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-4 py-6 text-center transition hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-white/15 dark:bg-white/5 dark:hover:border-emerald-500/60"
                >
                    <ImagePlus class="size-5 text-emerald-600" />
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ form.gallery.length > 0
                            ? `${form.gallery.length} gambar dipilih`
                            : 'Pilih gambar galeri' }}
                    </span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        Bisa pilih beberapa gambar sekaligus
                    </span>
                    <input
                        ref="galleryInput"
                        type="file"
                        class="sr-only"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp"
                        @change="pickGallery"
                    />
                </label>
                <InputError class="mt-1" :message="form.errors.gallery" />

                <div
                    v-if="(property?.images ?? []).length > 0"
                    class="mt-3"
                >
                    <p class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">
                        Gunakan tombol panah untuk mengatur urutan galeri.
                    </p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div
                            v-for="(image, index) in property?.images ?? []"
                            :key="image.id"
                            class="group relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-white/5"
                        >
                            <img
                                :src="image.url ?? ''"
                                :alt="image.alt ?? property?.name ?? ''"
                                class="h-24 w-full object-cover"
                            />
                            <div class="absolute top-1.5 left-1.5 flex gap-1">
                                <button
                                    type="button"
                                    :disabled="reordering || index === 0"
                                    class="inline-flex size-7 items-center justify-center rounded-lg bg-white/90 text-zinc-600 shadow-sm transition hover:bg-white disabled:opacity-40"
                                    aria-label="Naikkan gambar"
                                    @click="moveImage(index, -1)"
                                >
                                    <ChevronUp class="size-3.5" />
                                </button>
                                <button
                                    type="button"
                                    :disabled="
                                        reordering ||
                                        index ===
                                            (property?.images?.length ?? 1) - 1
                                    "
                                    class="inline-flex size-7 items-center justify-center rounded-lg bg-white/90 text-zinc-600 shadow-sm transition hover:bg-white disabled:opacity-40"
                                    aria-label="Turunkan gambar"
                                    @click="moveImage(index, 1)"
                                >
                                    <ChevronDown class="size-3.5" />
                                </button>
                            </div>
                            <button
                                type="button"
                                :disabled="imageForm.processing"
                                class="absolute top-1.5 right-1.5 inline-flex size-7 items-center justify-center rounded-lg bg-white/90 text-rose-600 shadow-sm transition hover:bg-white disabled:opacity-60"
                                aria-label="Hapus gambar"
                                @click="removeImage(image)"
                            >
                                <Trash2 class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4 dark:border-white/5">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Check class="size-4" />
                    {{ property ? 'Simpan perubahan' : 'Buat properti' }}
                </button>
                <Link
                    :href="propertiesIndex().url"
                    class="inline-flex h-10 items-center rounded-lg border border-zinc-200 px-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                >
                    {{ property ? 'Kembali' : 'Batal' }}
                </Link>
            </div>
        </form>

        <template v-if="property">
            <form
                v-if="admins"
                class="rounded-2xl border border-zinc-200/80 bg-white p-5 dark:border-white/10 dark:bg-zinc-900"
                @submit.prevent="submitAdmins"
            >
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                    Admin properti
                </h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    Pilih admin yang dapat memverifikasi pembayaran dan mengelola properti ini.
                </p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <label
                        v-for="admin in admins"
                        :key="admin.id"
                        class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-white/10 dark:text-zinc-200"
                    >
                        <input
                            v-model="adminsForm.admin_ids"
                            type="checkbox"
                            :value="admin.id"
                            class="accent-emerald-600"
                        />
                        <span>
                            {{ admin.name }}
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ admin.email }}</span>
                        </span>
                    </label>
                </div>
                <p v-if="admins.length === 0" class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Belum ada admin terdaftar. Buat akun admin lewat menu Pengguna.
                </p>
                <InputError class="mt-1" :message="adminsForm.errors.admin_ids" />
                <button
                    type="submit"
                    :disabled="adminsForm.processing"
                    class="mt-3 inline-flex h-9 items-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Check class="size-4" />
                    Simpan admin
                </button>
            </form>

            <section class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
                            Tipe kamar & tarif
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Setiap properti membutuhkan minimal satu tipe kamar dengan tarif aktif dan satu kamar tersedia.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-500"
                        @click="toggleRoomTypeForm"
                    >
                        <Plus class="size-4" />
                        Tipe kamar
                    </button>
                </div>

                <form
                    v-if="showRoomTypeForm"
                    class="grid gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 sm:grid-cols-[1.3fr_0.7fr_auto_auto] sm:items-end dark:border-emerald-500/20 dark:bg-emerald-500/5"
                    @submit.prevent="submitRoomType"
                >
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama tipe kamar</span>
                        <input
                            v-model="roomTypeForm.name"
                            type="text"
                            placeholder="Kamar Deluxe"
                            class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                        />
                        <InputError class="mt-1" :message="roomTypeForm.errors.name" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Kapasitas</span>
                        <input
                            v-model.number="roomTypeForm.capacity"
                            type="number"
                            min="1"
                            max="20"
                            class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                        />
                        <InputError class="mt-1" :message="roomTypeForm.errors.capacity" />
                    </label>
                    <button
                        type="submit"
                        :disabled="roomTypeForm.processing"
                        class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                    >
                        <Check class="size-4" />
                        Simpan
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-10 items-center rounded-lg border border-zinc-200 px-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300"
                        @click="toggleRoomTypeForm"
                    >
                        Batal
                    </button>
                    <label class="block sm:col-span-4">
                        <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Deskripsi (opsional)</span>
                        <textarea
                            v-model="roomTypeForm.description"
                            rows="2"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-zinc-900"
                        />
                        <InputError class="mt-1" :message="roomTypeForm.errors.description" />
                    </label>
                </form>

                <div class="space-y-3">
                    <RoomTypeEditor
                        v-for="roomType in property.room_types ?? []"
                        :key="roomType.id"
                        :property-id="property.id"
                        :room-type="roomType"
                    />
                </div>

                <p
                    v-if="(property.room_types ?? []).length === 0"
                    class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center text-sm text-zinc-500 dark:border-white/15 dark:bg-zinc-900 dark:text-zinc-400"
                >
                    Belum ada tipe kamar. Tambahkan tipe kamar beserta tarifnya sebelum publikasi.
                </p>
            </section>
        </template>
    </div>
</template>
