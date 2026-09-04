<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue';
import Icon from '@/components/Icon.vue';
import ServerStatsBadge from '@/components/ServerStatsBadge.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, updateAppearance } = useAppearance();
const isDark = computed(() => appearance.value === 'dark');
function toggleDarkMode() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-gray-200/80 bg-white/80 px-4 backdrop-blur-md transition-all md:px-6 dark:border-gray-800/80 dark:bg-gray-900/80"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <SidebarTrigger
                class="h-9 w-9 rounded-xl border border-gray-200/80 bg-white/80 p-2 text-gray-600 shadow-sm transition-all hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-700"
            />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <!-- Server Telemetry Badge -->
            <ServerStatsBadge class="hidden lg:block" />

            <!-- View Public Site Link -->
            <Link
                href="/"
                target="_blank"
                class="hidden items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white/80 px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 sm:inline-flex dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-200 dark:hover:bg-gray-700"
                title="Open Public Site in New Tab"
            >
                <Icon name="globe" class="h-3.5 w-3.5 text-blue-500" />
                <span>Public View</span>
            </Link>

            <!-- Dark Mode Toggle Button -->
            <button
                @click="toggleDarkMode"
                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-gray-200/80 bg-white/80 p-2 text-gray-600 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-700"
                :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                aria-label="Toggle dark mode"
            >
                <Icon :name="isDark ? 'sun' : 'moon'" class="h-4 w-4 rotate-0 transition-transform duration-300 dark:-rotate-12" />
            </button>
        </div>
    </header>
</template>
