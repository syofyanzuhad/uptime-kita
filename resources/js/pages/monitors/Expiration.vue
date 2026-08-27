<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Monitor, Paginator } from '@/types/monitor';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ExpirationStats {
    total: number;
    expired: number;
    expiring_soon: number;
}

const props = defineProps<{
    monitors: Paginator<Monitor>;
    stats: ExpirationStats;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Monitors',
        href: '/monitor',
    },
    {
        title: 'Domain Expiration',
        href: '/monitors/expiration',
    },
];

const formatDate = (date: string | null | undefined): string => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};

// Days left badge helpers
const getDaysLeftLabel = (daysLeft: number | null | undefined): string => {
    if (daysLeft === null || daysLeft === undefined) return '-';
    if (daysLeft < 0) return `Expired ${Math.abs(daysLeft)}d ago`;
    if (daysLeft === 0) return 'Expires today';
    return `${daysLeft} days left`;
};

const getDaysLeftColor = (daysLeft: number | null | undefined): string => {
    if (daysLeft === null || daysLeft === undefined) return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    if (daysLeft < 0) return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
    if (daysLeft <= 30) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300';
    return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300';
};

const getStatusBadgeColor = (status: string | undefined): string => {
    switch (status) {
        case 'up':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'down':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    }
};

const statCards = computed(() => [
    { label: 'Monitors Tracked', value: props.stats.total, icon: 'calendarClock', color: 'text-blue-600 dark:text-blue-400' },
    { label: 'Expired', value: props.stats.expired, icon: 'alertTriangle', color: 'text-red-600 dark:text-red-400' },
    { label: 'Expiring ≤ 30 Days', value: props.stats.expiring_soon, icon: 'clock', color: 'text-yellow-600 dark:text-yellow-400' },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Domain Expiration" />

        <div class="max-w-7xl mx-auto px-4 py-6 md:px-6 w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white sm:text-3xl">Domain Expiration Monitoring</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Track domain expiration deadlines, renew in advance, and prevent unexpected downtime.
                    </p>
                </div>
                <Link
                    href="/monitor/create"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-4 text-xs sm:text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700 hover:shadow-blue-500/30 active:scale-95"
                >
                    <Icon name="plus" class="h-4 w-4" />
                    <span>New Monitor</span>
                </Link>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="flex items-center gap-4 rounded-3xl border border-gray-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/80"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                        <Icon :name="card.icon" class="h-6 w-6" :class="card.color" />
                    </div>
                    <div>
                        <div class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ card.value }}</div>
                        <div class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ card.label }}</div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/80">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Active Domain Trackers</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sorted by soonest expiration date</p>
                    </div>
                </div>

                <div v-if="props.monitors.data.length === 0" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <Icon name="calendarClock" class="h-7 w-7" />
                    </div>
                    <p class="mt-3 text-base font-bold text-gray-900 dark:text-white">No domain expiration monitoring yet</p>
                    <p class="mt-1 text-xs max-w-sm mx-auto">Enable "Domain Expiration Check" when creating or editing a monitor to track its domain expiry.</p>
                    <Link href="/monitor/create" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        <span>Create a monitor</span>
                        <Icon name="arrowRight" class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50/50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3.5">Monitor</th>
                                <th class="px-6 py-3.5">Expiration Date</th>
                                <th class="px-6 py-3.5">Days Left</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Tags</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="monitor in props.monitors.data" :key="monitor.id" class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                                            <img
                                                v-if="monitor.favicon"
                                                :src="monitor.favicon"
                                                :alt="`${monitor.host} favicon`"
                                                class="h-4 w-4 rounded-sm object-contain"
                                                @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                                            />
                                            <Icon v-else name="globe" class="h-4 w-4 text-gray-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <Link
                                                :href="route('monitor.show', monitor.id)"
                                                class="block max-w-[200px] truncate font-bold text-gray-900 hover:text-blue-600 sm:max-w-xs dark:text-white dark:hover:text-blue-400"
                                            >
                                                {{ monitor.name }}
                                            </Link>
                                            <span class="block max-w-[200px] truncate text-xs text-gray-400 sm:max-w-xs">
                                                {{ monitor.url }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ formatDate(monitor.domain_expiration_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold whitespace-nowrap"
                                        :class="getDaysLeftColor(monitor.days_left)"
                                    >
                                        {{ getDaysLeftLabel(monitor.days_left) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider"
                                        :class="getStatusBadgeColor(monitor.uptime_status)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="monitor.uptime_status === 'up' ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'" />
                                        {{ monitor.uptime_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="tag in monitor.tags || []"
                                            :key="tag.id || tag.name"
                                            class="inline-flex items-center rounded-lg border border-gray-200/60 bg-gray-100/70 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                        >
                                            #{{ tag.name || tag }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.monitors.meta.last_page > 1" class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                    <Pagination :data="props.monitors" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
