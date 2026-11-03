<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, ShieldCheck, Trash2, UserRound } from '@lucide/vue';
import { ref } from 'vue';
import type { User, UserRole } from '@/types';
import { dashboard } from '@/routes/admin';
import { assign, destroy, index as usersIndex, store, update } from '@/routes/superadmin/users';

const props = defineProps<{
    users: User[];
    properties: Array<{ id: number; name: string }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Panel Admin', href: dashboard() },
            { title: 'Pengguna', href: usersIndex() },
        ],
    },
});

const editing = ref<User | null>(null);
const assigning = ref<User | null>(null);
const deleteForm = useForm({});

const form = useForm<{
    name: string;
    email: string;
    phone: string;
    role: UserRole;
    password: string;
}>({
    name: '',
    email: '',
    phone: '',
    role: 'customer',
    password: '',
});

const assignForm = useForm<{ property_ids: number[] }>({ property_ids: [] });

function startEdit(user: User): void {
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone ?? '';
    form.role = user.role;
    form.password = '';
}

function reset(): void {
    editing.value = null;
    form.reset();
}

function submit(): void {
    if (editing.value) {
        form.put(update(editing.value.id).url, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });

        return;
    }

    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => reset(),
    });
}

function startAssign(user: User): void {
    assigning.value = user;
    assignForm.property_ids = (user.assigned_properties ?? []).map((property) => property.id);
}

function submitAssign(): void {
    if (!assigning.value) {
        return;
    }

    assignForm.put(assign(assigning.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            assigning.value = null;
            assignForm.reset();
        },
    });
}

function remove(user: User): void {
    deleteForm.delete(destroy(user.id).url, { preserveScroll: true });
}

function roleLabel(role: UserRole): string {
    return role === 'superadmin' ? 'Superadmin' : role === 'admin' ? 'Admin properti' : 'Customer';
}
</script>

<template>
    <Head title="Pengguna — Superadmin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 lg:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Pengguna</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Kelola akun customer, admin properti, dan superadmin.
            </p>
        </div>

        <form
            class="grid gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 sm:grid-cols-2 xl:grid-cols-3 dark:border-white/10 dark:bg-zinc-900"
            @submit.prevent="submit"
        >
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Nama</span>
                <input v-model="form.name" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.name" class="mt-1 block text-xs text-rose-600">{{ form.errors.name }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Email</span>
                <input v-model="form.email" type="email" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.email" class="mt-1 block text-xs text-rose-600">{{ form.errors.email }}</span>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Telepon</span>
                <input v-model="form.phone" type="text" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">Peran</span>
                <select v-model="form.role" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin properti</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">
                    Password {{ editing ? '(kosongkan jika tidak diubah)' : '' }}
                </span>
                <input v-model="form.password" type="password" class="h-10 w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm dark:border-white/10 dark:bg-white/5" />
                <span v-if="form.errors.password" class="mt-1 block text-xs text-rose-600">{{ form.errors.password }}</span>
            </label>
            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60"
                >
                    <Plus class="size-4" />
                    {{ editing ? 'Simpan' : 'Tambah user' }}
                </button>
                <button v-if="editing" type="button" class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-300" @click="reset">
                    Batal
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
            <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                <li v-for="user in users" :key="user.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <UserRound class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ user.name }}
                                <span class="ml-2 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                                    {{ roleLabel(user.role) }}
                                </span>
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ user.email }} · {{ user.phone ?? 'tanpa telepon' }}
                            </p>
                            <p v-if="user.role !== 'customer'" class="mt-0.5 text-xs text-zinc-400">
                                Properti:
                                {{ (user.assigned_properties ?? []).map((property) => property.name).join(', ') || 'belum ditugaskan' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="user.role !== 'customer'"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200"
                            @click="startAssign(user)"
                        >
                            <ShieldCheck class="size-3.5" />
                            Properti
                        </button>
                        <button type="button" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-300 dark:border-white/10 dark:text-zinc-200" @click="startEdit(user)">
                            Ubah
                        </button>
                        <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:text-rose-400" @click="remove(user)">
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <form
            v-if="assigning"
            class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/5"
            @submit.prevent="submitAssign"
        >
            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                Penugasan properti untuk {{ assigning.name }}
            </p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <label
                    v-for="property in props.properties"
                    :key="property.id"
                    class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm dark:bg-zinc-900"
                >
                    <input v-model="assignForm.property_ids" type="checkbox" :value="property.id" class="accent-emerald-600" />
                    {{ property.name }}
                </label>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <button type="submit" :disabled="assignForm.processing" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-60">
                    Simpan penugasan
                </button>
                <button type="button" class="text-sm text-zinc-600 dark:text-zinc-300" @click="assigning = null">
                    Batal
                </button>
            </div>
        </form>
    </div>
</template>
