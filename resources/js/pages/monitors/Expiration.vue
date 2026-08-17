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

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div
                        v-for="card in statCards"
                        :key="card.label"
                        class="flex items-center gap-4 rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800"
                    >
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                            <Icon :name="card.icon" class="h-6 w-6" :class="card.color" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ card.value }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ card.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Domain Expiration Monitoring</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Monitors with domain expiration checking enabled, sorted by soonest expiration.
                        </p>
                    </div>

                    <div v-if="props.monitors.data.length === 0" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <Icon name="calendarClock" class="mx-auto mb-4 h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <p class="text-lg font-medium">No domain expiration monitoring yet</p>
                        <p class="mt-1 text-sm">Enable "Domain Expiration Check" when creating or editing a monitor to track its domain expiry.</p>
                        <Link href="/monitor/create" class="mt-4 inline-block text-blue-600 hover:underline dark:text-blue-400">
                            Create a monitor →
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Monitor</th>
                                    <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Expiration Date</th>
                                    <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Days Left</th>
                                    <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                                    <th class="px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Tags</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="monitor in props.monitors.data" :key="monitor.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img
                                                v-if="monitor.favicon"
                                                :src="monitor.favicon"
                                                :alt="`${monitor.host} favicon`"
                                                class="h-6 w-6 flex-shrink-0 rounded"
                                                @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                                            />
                                            <div class="min-w-0">
                                                <Link
                                                    :href="route('monitor.show', monitor.id)"
                                                    class="block max-w-[200px] truncate font-medium text-blue-600 hover:underline sm:max-w-xs dark:text-blue-400"
                                                >
                                                    {{ monitor.name }}
                                                </Link>
                                                <span class="block max-w-[200px] truncate text-xs text-gray-500 sm:max-w-xs dark:text-gray-400">
                                                    {{ monitor.url }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        {{ formatDate(monitor.domain_expiration_date) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap"
                                            :class="getDaysLeftColor(monitor.days_left)"
                                        >
                                            {{ getDaysLeftLabel(monitor.days_left) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-sm font-medium"
                                            :class="getStatusBadgeColor(monitor.uptime_status)"
                                        >
                                            {{ monitor.uptime_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="tag in monitor.tags || []"
                                                :key="tag.id || tag.name"
                                                class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ tag.name || tag }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="props.monitors.meta.last_page > 1" class="px-6 py-4">
                        <Pagination :data="props.monitors" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
