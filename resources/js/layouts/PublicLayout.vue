<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, LogIn, Ticket } from '@lucide/vue';
import { computed } from 'vue';
import KosanLogo from '@/components/KosanLogo.vue';
import { Toaster } from '@/components/ui/sonner';
import { dashboard, login, register } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as bookingsIndex } from '@/routes/bookings';
import { show as showPage } from '@/routes/pages';
import { index as propertiesIndex } from '@/routes/properties';
import type { Page } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const pages = computed<Page[]>(
    () => (page.props.pages as Page[] | undefined) ?? [],
);

const dashboardHref = computed(() =>
    user.value && user.value.role !== 'customer'
        ? adminDashboard().url
        : dashboard().url,
);

const isStaff = computed(
    () => user.value !== null && user.value.role !== 'customer',
);
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-[#fafaf8] text-zinc-900 dark:bg-[#0b0d0c] dark:text-zinc-100"
    >
        <header
            class="sticky top-0 z-40 border-b border-zinc-200/70 bg-[#fafaf8]/85 backdrop-blur-lg dark:border-white/10 dark:bg-[#0b0d0c]/85"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6"
            >
                <KosanLogo />

                <nav class="hidden items-center gap-1 md:flex">
                    <Link
                        :href="propertiesIndex()"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white"
                    >
                        Cari Penginapan
                    </Link>
                    <Link
                        :href="propertiesIndex.url({ query: { type: 'kos' } })"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white"
                    >
                        Kos
                    </Link>
                    <Link
                        :href="propertiesIndex.url({ query: { type: 'hotel' } })"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white"
                    >
                        Hotel
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <template v-if="user">
                        <Link
                            v-if="!isStaff"
                            :href="bookingsIndex()"
                            class="hidden items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 sm:flex dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white"
                        >
                            <Ticket class="size-4" />
                            Pesanan
                        </Link>
                        <Link
                            :href="dashboardHref"
                            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                        >
                            <LayoutDashboard class="size-4" />
                            {{ isStaff ? 'Panel Admin' : 'Dashboard' }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/10"
                        >
                            <LogIn class="size-4" />
                            Masuk
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-emerald-600/20 transition hover:bg-emerald-500"
                        >
                            Daftar
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-zinc-200/70 dark:border-white/10">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-10 sm:px-6 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-2">
                    <KosanLogo />
                    <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                        Booking hotel dan kos multi-kota dengan tarif per tipe
                        kamar, cicilan, dan refund yang transparan.
                    </p>
                </div>
                <div
                    class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400"
                >
                    <Link :href="propertiesIndex()" class="hover:text-emerald-600">
                        Cari Penginapan
                    </Link>
                    <Link
                        v-for="item in pages"
                        :key="item.slug"
                        :href="showPage(item.slug)"
                        class="hover:text-emerald-600"
                    >
                        {{ item.title }}
                    </Link>
                    <template v-if="!user">
                        <Link :href="login()" class="hover:text-emerald-600">
                            Masuk
                        </Link>
                        <Link :href="register()" class="hover:text-emerald-600">
                            Daftar
                        </Link>
                    </template>
                </div>
            </div>
        </footer>

        <Toaster />
    </div>
</template>
