<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import Input from '@/components/ui/input/Input.vue';
import Select from '@/components/ui/input/Select.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Monitor, Paginator } from '@/types/monitor';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface ExpirationStats {
    total: number;
    expired: number;
    expiring_soon: number;
}

const props = defineProps<{
    monitors: Paginator<Monitor>;
    stats: ExpirationStats;
    search?: string;
    statusFilter?: string;
    uptimeFilter?: string;
    tagFilter?: string;
    perPage?: number;
    availableTags?: Array<{ id: number; name: string }>;
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

// Search and filter states
const searchQuery = ref(props.search || '');
const statusFilter = ref(props.statusFilter || 'all');
const uptimeFilter = ref(props.uptimeFilter || 'all');
const tagFilter = ref(props.tagFilter || '');
const perPage = ref(props.perPage || 50);

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value !== '' ||
        statusFilter.value !== 'all' ||
        uptimeFilter.value !== 'all' ||
        tagFilter.value !== '' ||
        perPage.value !== 50
    );
});

const applyFilters = () => {
    router.get(
        '/monitors/expiration',
        {
            search: searchQuery.value || undefined,
            status_filter: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            uptime_filter: uptimeFilter.value !== 'all' ? uptimeFilter.value : undefined,
            tag_filter: tagFilter.value || undefined,
            per_page: perPage.value !== 50 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    uptimeFilter.value = 'all';
    tagFilter.value = '';
    perPage.value = 50;
    applyFilters();
};

const filterByStat = (filterType: string) => {
    statusFilter.value = statusFilter.value === filterType ? 'all' : filterType;
    applyFilters();
};

// Watch for select filter changes
watch([statusFilter, uptimeFilter, tagFilter, perPage], () => {
    applyFilters();
});

// Debounced search
let searchTimeout: number;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        applyFilters();
    }, 300);
});

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
    {
        key: 'all',
        label: 'Monitors Tracked',
        value: props.stats.total,
        icon: 'calendarClock',
        color: 'text-blue-600 dark:text-blue-400',
        active: statusFilter.value === 'all',
    },
    {
        key: 'expired',
        label: 'Expired',
        value: props.stats.expired,
        icon: 'alertTriangle',
        color: 'text-red-600 dark:text-red-400',
        active: statusFilter.value === 'expired',
    },
    {
        key: 'expiring_soon',
        label: 'Expiring ≤ 30 Days',
        value: props.stats.expiring_soon,
        icon: 'clock',
        color: 'text-yellow-600 dark:text-yellow-400',
        active: statusFilter.value === 'expiring_soon',
    },
]);

const tagOptions = computed(() => [
    { label: 'All Tags', value: '' },
    ...(props.availableTags || []).map((t) => ({ label: `#${t.name}`, value: t.name })),
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Domain Expiration" />

        <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 md:px-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">Domain Expiration Monitoring</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Track domain expiration deadlines, renew in advance, and prevent unexpected downtime.
                    </p>
                </div>
                <Link
                    href="/monitor/create"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700 hover:shadow-blue-500/30 active:scale-95 sm:text-sm"
                >
                    <Icon name="plus" class="h-4 w-4" />
                    <span>New Monitor</span>
                </Link>
            </div>

            <!-- Summary Cards (Interactive) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <button
                    v-for="card in statCards"
                    :key="card.label"
                    type="button"
                    @click="filterByStat(card.key)"
                    class="flex items-center gap-4 rounded-3xl border p-5 text-left shadow-sm transition-all backdrop-blur-sm hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
                    :class="
                        card.active
                            ? 'border-blue-500/80 bg-blue-50/40 ring-2 ring-blue-500/30 dark:border-blue-500/80 dark:bg-blue-950/20'
                            : 'border-gray-200/80 bg-white/80 dark:border-gray-800 dark:bg-gray-900/80'
                    "
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                        <Icon :name="card.icon" class="h-6 w-6" :class="card.color" />
                    </div>
                    <div>
                        <div class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ card.value }}</div>
                        <div class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">{{ card.label }}</div>
                    </div>
                </button>
            </div>

            <!-- Filters Section -->
            <div class="rounded-3xl border border-gray-200/80 bg-white/80 p-4 shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/80">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">Search</label>
                        <Input v-model="searchQuery" placeholder="Search domain, host, or name..." class="h-10 w-full rounded-2xl" />
                    </div>

                    <!-- Expiration Status Filter -->
                    <div>
                        <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">Expiry Status</label>
                        <Select
                            v-model="statusFilter"
                            :items="[
                                { label: 'All Expiry', value: 'all' },
                                { label: 'Expiring ≤ 30 Days', value: 'expiring_soon' },
                                { label: 'Expired', value: 'expired' },
                                { label: 'Healthy (> 30 Days)', value: 'healthy' },
                            ]"
                            placeholder="All expiry"
                            class="h-10 rounded-2xl"
                        />
                    </div>

                    <!-- Uptime Status Filter -->
                    <div>
                        <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">Uptime Status</label>
                        <Select
                            v-model="uptimeFilter"
                            :items="[
                                { label: 'All Uptime', value: 'all' },
                                { label: 'Up (Online)', value: 'up' },
                                { label: 'Down (Offline)', value: 'down' },
                            ]"
                            placeholder="All uptime"
                            class="h-10 rounded-2xl"
                        />
                    </div>

                    <!-- Tag Filter -->
                    <div>
                        <label class="mb-1.5 block text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">Tag</label>
                        <Select
                            v-model="tagFilter"
                            :items="tagOptions"
                            placeholder="All tags"
                            class="h-10 rounded-2xl"
                        />
                    </div>
                </div>

                <!-- Active Filter Reset Bar -->
                <div v-if="hasActiveFilters" class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Filtering active results
                    </span>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer"
                    >
                        <Icon name="x" class="h-3.5 w-3.5" />
                        <span>Clear all filters</span>
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div
                class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/80"
            >
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Active Domain Trackers</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ props.monitors.meta.total }} monitor{{ props.monitors.meta.total === 1 ? '' : 's' }} &middot; Sorted by soonest expiration date
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-500 dark:text-gray-400">Per page:</label>
                        <Select
                            v-model="perPage"
                            :items="[
                                { label: '25', value: 25 },
                                { label: '50', value: 50 },
                                { label: '100', value: 100 },
                            ]"
                            class="h-8 w-20 rounded-xl text-xs"
                        />
                    </div>
                </div>

                <div v-if="props.monitors.data.length === 0" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <Icon name="calendarClock" class="h-7 w-7" />
                    </div>
                    <template v-if="hasActiveFilters">
                        <p class="mt-3 text-base font-bold text-gray-900 dark:text-white">No matching monitors found</p>
                        <p class="mx-auto mt-1 max-w-sm text-xs">
                            No domain expiration monitors match your current search or filter criteria.
                        </p>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 cursor-pointer"
                        >
                            <span>Clear filters</span>
                        </button>
                    </template>
                    <template v-else>
                        <p class="mt-3 text-base font-bold text-gray-900 dark:text-white">No domain expiration monitoring yet</p>
                        <p class="mx-auto mt-1 max-w-sm text-xs">
                            Enable "Domain Expiration Check" when creating or editing a monitor to track its domain expiry.
                        </p>
                        <Link
                            href="/monitor/create"
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                        >
                            <span>Create a monitor</span>
                            <Icon name="arrowRight" class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b border-gray-100 bg-gray-50/50 text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5">Monitor</th>
                                <th class="px-6 py-3.5">Expiration Date</th>
                                <th class="px-6 py-3.5">Days Left</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Tags</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="monitor in props.monitors.data"
                                :key="monitor.id"
                                class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40"
                            >
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
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold tracking-wider uppercase"
                                        :class="getStatusBadgeColor(monitor.uptime_status)"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="monitor.uptime_status === 'up' ? 'animate-pulse bg-emerald-500' : 'bg-rose-500'"
                                        />
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

