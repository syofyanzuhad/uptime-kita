<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    BellOff,
    CheckCircle2,
    Plus,
    RefreshCw,
    Search,
    ShieldAlert,
    ShieldCheck,
    Users,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
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

onMounted(() => {
    fetchMonitorStatistics();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            <!-- Header & Action Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Monitor Dashboard</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Live service status, uptime performance, and health checks across all endpoints.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="fetchMonitorStatistics"
                        :disabled="loadingMonitors"
                        class="gap-1.5 h-9 font-medium"
                    >
                        <RefreshCw class="h-4 w-4" :class="loadingMonitors ? 'animate-spin' : ''" />
                        <span class="hidden sm:inline">Refresh Stats</span>
                    </Button>
                    <Link
                        v-if="isAuthenticated"
                        :href="route('monitor.create')"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3.5 text-xs sm:text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        <span>New Monitor</span>
                    </Link>
                </div>
            </div>

            <!-- Error Banner -->
            <div
                v-if="errorMonitors"
                class="flex items-center justify-between rounded-xl border border-destructive/20 bg-destructive/10 p-4 text-sm text-destructive"
            >
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="h-5 w-5 shrink-0" />
                    <span>{{ errorMonitors }}</span>
                </div>
                <Button size="sm" variant="outline" @click="fetchMonitorStatistics">Retry</Button>
            </div>

            <!-- KPI Metric Stat Cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                <!-- Total Monitors Card -->
                <button
                    type="button"
                    @click="statusFilter = 'all'"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm"
                    :class="[
                        statusFilter === 'all'
                            ? 'border-primary/50 bg-primary/5 ring-2 ring-primary/20 dark:bg-primary/10'
                            : 'border-border/80 bg-card hover:border-border',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Total Monitors</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Activity class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold tracking-tight text-foreground">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-muted"></span>
                            <span v-else>{{ allCount }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">All monitored endpoints</p>
                    </div>
                </button>

                <!-- Online Card -->
                <button
                    type="button"
                    @click="statusFilter = 'up'"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm"
                    :class="[
                        statusFilter === 'up'
                            ? 'border-emerald-500/50 bg-emerald-50/60 ring-2 ring-emerald-500/20 dark:bg-emerald-950/30'
                            : 'border-border/80 bg-card hover:border-border',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Online</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-muted"></span>
                            <span v-else class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ onlineCount }}</span>
                            <span v-if="!loadingMonitors && allCount > 0" class="text-xs font-medium text-emerald-600/80 dark:text-emerald-400/80">
                                {{ onlinePercentage }}%
                            </span>
                        </div>
                        <div class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
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
                    class="group relative flex flex-col justify-between overflow-hidden rounded-xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm"
                    :class="[
                        statusFilter === 'down'
                            ? 'border-rose-500/50 bg-rose-50/60 ring-2 ring-rose-500/20 dark:bg-rose-950/30'
                            : 'border-border/80 bg-card hover:border-border',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-rose-700 dark:text-rose-400">Offline</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                            <XCircle class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-muted"></span>
                            <span v-else>{{ offlineCount }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            <span v-if="offlineCount > 0" class="text-rose-500 font-medium">Requires attention</span>
                            <span v-else>No reported outages</span>
                        </p>
                    </div>
                </button>

                <!-- Monitored / Enabled Card -->
                <button
                    type="button"
                    @click="statusFilter = 'globally_enabled'"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-xl border p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm"
                    :class="[
                        statusFilter === 'globally_enabled'
                            ? 'border-blue-500/50 bg-blue-50/60 ring-2 ring-blue-500/20 dark:bg-blue-950/30'
                            : 'border-border/80 bg-card hover:border-border',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-blue-700 dark:text-blue-400">Active Checks</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                            <span v-if="loadingMonitors" class="inline-block h-7 w-12 animate-pulse rounded bg-muted"></span>
                            <span v-else>{{ enabledCount }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            <span>{{ disabledCount }} paused</span>
                        </p>
                    </div>
                </button>

                <!-- Admin Users Card (if admin) -->
                <div
                    v-if="userId === 1 && userCount !== null"
                    class="flex flex-col justify-between rounded-xl border border-border/80 bg-card p-4 transition-all"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Total Users</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold tracking-tight text-foreground">
                            {{ userCount }}
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">Registered accounts</p>
                    </div>
                </div>
            </div>

            <!-- Toolbar: Filter Pills & Search Input -->
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5 p-1 rounded-xl bg-muted/40 border border-border/60">
                    <button
                        type="button"
                        @click="statusFilter = 'all'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="[
                            statusFilter === 'all'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60',
                        ]"
                    >
                        <Activity class="h-3.5 w-3.5" />
                        <span>All</span>
                        <span
                            class="rounded-full px-1.5 py-0.2 text-[11px] font-semibold"
                            :class="statusFilter === 'all' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                        >
                            {{ allCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'up'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="[
                            statusFilter === 'up'
                                ? 'bg-background text-emerald-700 dark:text-emerald-400 shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60',
                        ]"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                        <span>Online</span>
                        <span
                            class="rounded-full px-1.5 py-0.2 text-[11px] font-semibold"
                            :class="statusFilter === 'up' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-muted text-muted-foreground'"
                        >
                            {{ onlineCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'down'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="[
                            statusFilter === 'down'
                                ? 'bg-background text-rose-700 dark:text-rose-400 shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60',
                        ]"
                    >
                        <XCircle class="h-3.5 w-3.5 text-rose-500" />
                        <span>Offline</span>
                        <span
                            class="rounded-full px-1.5 py-0.2 text-[11px] font-semibold"
                            :class="statusFilter === 'down' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-muted text-muted-foreground'"
                        >
                            {{ offlineCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'unsubscribed'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="[
                            statusFilter === 'unsubscribed'
                                ? 'bg-background text-amber-700 dark:text-amber-400 shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60',
                        ]"
                    >
                        <BellOff class="h-3.5 w-3.5 text-amber-500" />
                        <span>Unsubscribed</span>
                        <span
                            class="rounded-full px-1.5 py-0.2 text-[11px] font-semibold"
                            :class="statusFilter === 'unsubscribed' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-muted text-muted-foreground'"
                        >
                            {{ unsubscribedCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'globally_enabled'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="[
                            statusFilter === 'globally_enabled'
                                ? 'bg-background text-blue-700 dark:text-blue-400 shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60',
                        ]"
                    >
                        <ShieldCheck class="h-3.5 w-3.5 text-blue-500" />
                        <span>Enabled</span>
                        <span
                            class="rounded-full px-1.5 py-0.2 text-[11px] font-semibold"
                            :class="statusFilter === 'globally_enabled' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-muted text-muted-foreground'"
                        >
                            {{ enabledCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'globally_disabled'"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-all"
                        :class="[
                            statusFilter === 'globally_disabled'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60',
                        ]"
                    >
                        <ShieldAlert class="h-3.5 w-3.5 text-gray-500" />
                        <span>Disabled</span>
                        <span
                            class="rounded-full px-1.5 py-0.2 text-[11px] font-semibold"
                            :class="statusFilter === 'globally_disabled' ? 'bg-muted text-foreground' : 'bg-muted text-muted-foreground'"
                        >
                            {{ disabledCount }}
                        </span>
                    </button>
                </div>

                <!-- Search Input & Reset Button -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:w-80">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search domain or URL..."
                            class="h-9 w-full rounded-lg border border-input bg-background pl-9 pr-8 text-xs sm:text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
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
                        class="h-9 text-xs text-muted-foreground hover:text-foreground shrink-0"
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

