<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Activity, AlertCircle, BellOff, CheckCircle2, Plus, RefreshCw, Search, ShieldAlert, ShieldCheck, Users, X, XCircle } from 'lucide-vue-next';
import { usePollMode } from '@/composables/usePollMode';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import PinnedMonitorsCard from '../components/PinnedMonitorsCard.vue';
import PrivateMonitorsCard from '../components/PrivateMonitorsCard.vue';
import PublicMonitorsCard from '../components/PublicMonitorsCard.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const searchQuery = ref('');
const statusFilter = ref<'all' | 'up' | 'down' | 'unsubscribed' | 'globally_enabled' | 'globally_disabled'>('all');

// Monitor data for counts
const loadingMonitors = ref(false);
const errorMonitors = ref<string | null>(null);
const allCount = ref(0);
const onlineCount = ref(0);
const offlineCount = ref(0);
const unsubscribedCount = ref(0);
const enabledCount = ref(0);
const disabledCount = ref(0);
const userId = computed(() => (page.props as any).auth?.user?.id);
const userCount = ref<number | null>(null);

const page = usePage();
const isAuthenticated = computed(() => !!(page.props as any).auth?.user);

const onlinePercentage = computed(() => {
    if (allCount.value === 0) return 100;
    return Math.round((onlineCount.value / allCount.value) * 100);
});

const isFilterActive = computed(() => {
    return statusFilter.value !== 'all' || searchQuery.value.trim().length > 0;
});

function resetFilters() {
    statusFilter.value = 'all';
    searchQuery.value = '';
}

async function fetchMonitorStatistics() {
    loadingMonitors.value = true;
    try {
        const response = await fetch('/statistic-monitor');
        if (!response.ok) throw new Error('Failed to fetch monitor statistics');
        const stats = await response.json();

        if (isAuthenticated.value) {
            allCount.value = stats.total_monitors;
        } else {
            allCount.value = stats.public_monitor_count;
        }
        onlineCount.value = stats.online_monitors;
        offlineCount.value = stats.offline_monitors;
        unsubscribedCount.value = stats.unsubscribed_monitors;
        enabledCount.value = stats.globally_enabled_monitors || 0;
        disabledCount.value = stats.globally_disabled_monitors || 0;
        if ('user_count' in stats) {
            userCount.value = stats.user_count;
        } else {
            userCount.value = null;
        }

        errorMonitors.value = null;
    } catch (err) {
        errorMonitors.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loadingMonitors.value = false;
    }
}

const { isAutoPolling } = usePollMode();
let statsInterval: ReturnType<typeof setInterval> | null = null;

function startStatsPolling() {
    if (!isAutoPolling.value || statsInterval) return;
    statsInterval = setInterval(() => {
        if (!loadingMonitors.value) {
            fetchMonitorStatistics();
        }
    }, 60000);
}

function stopStatsPolling() {
    if (statsInterval) {
        clearInterval(statsInterval);
        statsInterval = null;
    }
}

onMounted(() => {
    fetchMonitorStatistics();
    startStatsPolling();
});

onUnmounted(() => {
    stopStatsPolling();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header & Action Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">Monitor Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Live service status, uptime performance, and health checks across all endpoints.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="fetchMonitorStatistics"
                        :disabled="loadingMonitors"
                        class="h-9 gap-1.5 rounded-xl border-gray-200/80 bg-white/80 font-semibold shadow-xs hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800/80 dark:hover:bg-gray-700"
                    >
                        <RefreshCw class="h-4 w-4" :class="loadingMonitors ? 'animate-spin' : ''" />
                        <span class="hidden sm:inline">Refresh Stats</span>
                    </Button>
                    <Link
                        v-if="isAuthenticated"
                        :href="route('monitor.create')"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700 hover:shadow-blue-500/30 active:scale-95 sm:text-sm"
                    >
                        <Plus class="h-4 w-4" />
                        <span>New Monitor</span>
                    </Link>
                </div>
            </div>

            <!-- Error Banner -->
            <div
                v-if="errorMonitors"
                class="flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-700 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="h-5 w-5 shrink-0 text-rose-600" />
                    <span>{{ errorMonitors }}</span>
                </div>
                <Button size="sm" variant="outline" class="rounded-xl" @click="fetchMonitorStatistics">Retry</Button>
            </div>

            <!-- KPI Metric Stat Cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                <!-- Total Monitors Card -->
                <button
                    type="button"
                    @click="statusFilter = 'all'"
                    class="group relative flex cursor-pointer flex-col justify-between overflow-hidden rounded-2xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    :class="[
                        statusFilter === 'all'
                            ? 'border-blue-500/60 bg-blue-50/70 shadow-sm ring-2 ring-blue-500/20 dark:border-blue-500/40 dark:bg-blue-950/40'
                            : 'border-gray-200/80 bg-white/80 shadow-xs hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/80 dark:hover:border-gray-700',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">Total</span>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 transition-transform group-hover:scale-105 dark:bg-blue-500/20 dark:text-blue-400"
                        >
                            <Activity class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-gray-200 dark:bg-gray-800"></span>
                            <span v-else>{{ allCount }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Monitored endpoints</p>
                    </div>
                </button>

                <!-- Online Card -->
                <button
                    type="button"
                    @click="statusFilter = 'up'"
                    class="group relative flex cursor-pointer flex-col justify-between overflow-hidden rounded-2xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    :class="[
                        statusFilter === 'up'
                            ? 'border-emerald-500/60 bg-emerald-50/70 shadow-sm ring-2 ring-emerald-500/20 dark:border-emerald-500/40 dark:bg-emerald-950/40'
                            : 'border-gray-200/80 bg-white/80 shadow-xs hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/80 dark:hover:border-gray-700',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold tracking-wider text-emerald-700 uppercase dark:text-emerald-400">Online</span>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 transition-transform group-hover:scale-105 dark:bg-emerald-500/20 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-gray-200 dark:bg-gray-800"></span>
                            <span v-else class="text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">{{ onlineCount }}</span>
                            <span v-if="!loadingMonitors && allCount > 0" class="text-xs font-bold text-emerald-600/80 dark:text-emerald-400/80">
                                {{ onlinePercentage }}%
                            </span>
                        </div>
                        <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            <span>Operating normally</span>
                        </div>
                    </div>
                </button>

                <!-- Offline Card -->
                <button
                    type="button"
                    @click="statusFilter = 'down'"
                    class="group relative flex cursor-pointer flex-col justify-between overflow-hidden rounded-2xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    :class="[
                        statusFilter === 'down'
                            ? 'border-rose-500/60 bg-rose-50/70 shadow-sm ring-2 ring-rose-500/20 dark:border-rose-500/40 dark:bg-rose-950/40'
                            : 'border-gray-200/80 bg-white/80 shadow-xs hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/80 dark:hover:border-gray-700',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold tracking-wider text-rose-700 uppercase dark:text-rose-400">Offline</span>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 transition-transform group-hover:scale-105 dark:bg-rose-500/20 dark:text-rose-400"
                        >
                            <XCircle class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-gray-200 dark:bg-gray-800"></span>
                            <span v-else>{{ offlineCount }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="offlineCount > 0" class="font-bold text-rose-500">Needs attention</span>
                            <span v-else>No reported outages</span>
                        </p>
                    </div>
                </button>

                <!-- Monitored / Enabled Card -->
                <button
                    type="button"
                    @click="statusFilter = 'globally_enabled'"
                    class="group relative flex cursor-pointer flex-col justify-between overflow-hidden rounded-2xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                    :class="[
                        statusFilter === 'globally_enabled'
                            ? 'border-blue-500/60 bg-blue-50/70 shadow-sm ring-2 ring-blue-500/20 dark:border-blue-500/40 dark:bg-blue-950/40'
                            : 'border-gray-200/80 bg-white/80 shadow-xs hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/80 dark:hover:border-gray-700',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold tracking-wider text-blue-700 uppercase dark:text-blue-400">Active Checks</span>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 transition-transform group-hover:scale-105 dark:bg-blue-500/20 dark:text-blue-400"
                        >
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black tracking-tight text-blue-600 dark:text-blue-400">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-gray-200 dark:bg-gray-800"></span>
                            <span v-else>{{ enabledCount }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ disabledCount }} paused</span>
                        </p>
                    </div>
                </button>

                <!-- Admin Users Card (if admin) -->
                <div
                    v-if="userId === 1 && userCount !== null"
                    class="flex flex-col justify-between rounded-2xl border border-gray-200/80 bg-white/80 p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900/80"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold tracking-wider text-purple-700 uppercase dark:text-purple-400">Total Users</span>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400"
                        >
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            {{ userCount }}
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Registered accounts</p>
                    </div>
                </div>
            </div>

            <!-- Toolbar: Filter Pills & Search Input -->
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <!-- Status Filter Pills -->
                <div
                    class="flex flex-wrap items-center gap-1 rounded-2xl border border-gray-200/80 bg-gray-100/80 p-1 backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/80"
                >
                    <button
                        type="button"
                        @click="statusFilter = 'all'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider uppercase transition-all"
                        :class="[
                            statusFilter === 'all'
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        ]"
                    >
                        <Activity class="h-3.5 w-3.5" />
                        <span>All</span>
                        <span
                            class="py-0.2 rounded-full px-1.5 text-[10px] font-extrabold"
                            :class="
                                statusFilter === 'all'
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                    : 'bg-gray-200/80 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ allCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'up'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider uppercase transition-all"
                        :class="[
                            statusFilter === 'up'
                                ? 'bg-white text-emerald-700 shadow-sm dark:bg-gray-800 dark:text-emerald-400'
                                : 'text-gray-500 hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400',
                        ]"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                        <span>Online</span>
                        <span
                            class="py-0.2 rounded-full px-1.5 text-[10px] font-extrabold"
                            :class="
                                statusFilter === 'up'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-gray-200/80 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ onlineCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'down'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider uppercase transition-all"
                        :class="[
                            statusFilter === 'down'
                                ? 'bg-white text-rose-700 shadow-sm dark:bg-gray-800 dark:text-rose-400'
                                : 'text-gray-500 hover:text-rose-600 dark:text-gray-400 dark:hover:text-rose-400',
                        ]"
                    >
                        <XCircle class="h-3.5 w-3.5 text-rose-500" />
                        <span>Offline</span>
                        <span
                            class="py-0.2 rounded-full px-1.5 text-[10px] font-extrabold"
                            :class="
                                statusFilter === 'down'
                                    ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                    : 'bg-gray-200/80 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ offlineCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'unsubscribed'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider uppercase transition-all"
                        :class="[
                            statusFilter === 'unsubscribed'
                                ? 'bg-white text-amber-700 shadow-sm dark:bg-gray-800 dark:text-amber-400'
                                : 'text-gray-500 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400',
                        ]"
                    >
                        <BellOff class="h-3.5 w-3.5 text-amber-500" />
                        <span>Unsubscribed</span>
                        <span
                            class="py-0.2 rounded-full px-1.5 text-[10px] font-extrabold"
                            :class="
                                statusFilter === 'unsubscribed'
                                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                    : 'bg-gray-200/80 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ unsubscribedCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'globally_enabled'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider uppercase transition-all"
                        :class="[
                            statusFilter === 'globally_enabled'
                                ? 'bg-white text-blue-700 shadow-sm dark:bg-gray-800 dark:text-blue-400'
                                : 'text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400',
                        ]"
                    >
                        <ShieldCheck class="h-3.5 w-3.5 text-blue-500" />
                        <span>Enabled</span>
                        <span
                            class="py-0.2 rounded-full px-1.5 text-[10px] font-extrabold"
                            :class="
                                statusFilter === 'globally_enabled'
                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300'
                                    : 'bg-gray-200/80 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ enabledCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'globally_disabled'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold tracking-wider uppercase transition-all"
                        :class="[
                            statusFilter === 'globally_disabled'
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        ]"
                    >
                        <ShieldAlert class="h-3.5 w-3.5 text-gray-500" />
                        <span>Disabled</span>
                        <span
                            class="py-0.2 rounded-full px-1.5 text-[10px] font-extrabold"
                            :class="
                                statusFilter === 'globally_disabled'
                                    ? 'bg-gray-300 text-gray-800 dark:bg-gray-600 dark:text-gray-100'
                                    : 'bg-gray-200/80 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ disabledCount }}
                        </span>
                    </button>
                </div>

                <!-- Search Input & Reset Button -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-80">
                        <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search domain or URL... (/)"
                            class="h-10 w-full rounded-2xl border border-gray-200/80 bg-white/80 pr-8 pl-9 text-xs text-gray-900 shadow-xs backdrop-blur-sm transition-all placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none sm:text-sm dark:border-gray-800 dark:bg-gray-900/80 dark:text-white dark:placeholder:text-gray-500"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            title="Clear search"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <Button
                        v-if="isFilterActive"
                        variant="ghost"
                        size="sm"
                        @click="resetFilters"
                        class="h-10 shrink-0 rounded-2xl text-xs font-semibold text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >
                        <X class="mr-1 h-3.5 w-3.5" />
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Monitor Cards Sections -->
            <div class="space-y-6">
                <!-- Pinned Monitors (Authenticated only) -->
                <PinnedMonitorsCard
                    v-if="isAuthenticated"
                    :search-query="searchQuery"
                    :status-filter="statusFilter"
                    :all-count="allCount"
                    :online-count="onlineCount"
                    :offline-count="offlineCount"
                    :unsubscribed-count="unsubscribedCount"
                    :disabled-count="disabledCount"
                    :enabled-count="enabledCount"
                />

                <!-- Private Monitors (Authenticated only) -->
                <PrivateMonitorsCard
                    v-if="isAuthenticated"
                    :search-query="searchQuery"
                    :status-filter="statusFilter"
                    :all-count="allCount"
                    :online-count="onlineCount"
                    :offline-count="offlineCount"
                    :unsubscribed-count="unsubscribedCount"
                    :disabled-count="disabledCount"
                    :enabled-count="enabledCount"
                />

                <!-- Public Monitors -->
                <PublicMonitorsCard
                    :search-query="searchQuery"
                    :status-filter="statusFilter"
                    :all-count="allCount"
                    :online-count="onlineCount"
                    :offline-count="offlineCount"
                    :unsubscribed-count="unsubscribedCount"
                    :disabled-count="disabledCount"
                    :enabled-count="enabledCount"
                />
            </div>
        </div>
    </AppLayout>
</template>
