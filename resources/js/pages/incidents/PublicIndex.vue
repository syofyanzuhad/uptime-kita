<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { formatRelativeTime } from '@/composables/useMonitorHelpers';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Incident {
    id: number;
    monitor_id: number;
    type: string;
    started_at: string;
    ended_at: string | null;
    duration_minutes: number | null;
    reason: string | null;
    response_time: number | null;
    status_code: number | null;
    monitor: {
        id: number;
        url: string;
        display_name: string | null;
        is_public: boolean;
        uptime_status: string;
        raw_url: string;
    };
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedIncidents {
    data: Incident[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLinks[];
}

interface Props {
    incidents: PaginatedIncidents;
    filters: {
        search: string;
        status: string;
        range: string;
    };
    stats: {
        ongoing_count: number;
        resolved_30d: number;
        avg_duration_minutes: number;
        total_public_monitors: number;
    };
    appUrl: string;
}

const props = defineProps<Props>();

const pageTitle = computed(() => `Public Incident History - Uptime Kita`);
const pageDescription = computed(
    () => `View real-time and historical incident reports across ${props.stats.total_public_monitors} public monitored websites.`,
);
const shareUrl = computed(() => `${props.appUrl}/incidents`);
const shareText = computed(() => `Check out public uptime incidents and reliability history on Uptime Kita.`);
const ogImage = computed(() => `${props.appUrl}/og/monitors.png`);
const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    name: 'Uptime Kita - Incident History',
    url: shareUrl.value,
    description: pageDescription.value,
}));

// Local filter state
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const rangeFilter = ref(props.filters.range || '30d');
const searchInputRef = ref<HTMLInputElement | null>(null);

let searchTimer: number | null = null;
function debounceSearch() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => applyFilters(), 300);
}

function applyFilters() {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
    if (rangeFilter.value !== '30d') params.append('range', rangeFilter.value);

    router.visit(`/incidents?${params.toString()}`, { preserveState: true, replace: true });
}

function filterByStatus(val: string) {
    statusFilter.value = val;
    applyFilters();
}

function resetFilters() {
    searchQuery.value = '';
    statusFilter.value = 'all';
    rangeFilter.value = '30d';
    applyFilters();
}

const hasActiveFilters = computed(() => !!searchQuery.value || statusFilter.value !== 'all' || rangeFilter.value !== '30d');

function getMonitorCleanDomain(monitor: Incident['monitor']): string {
    const raw = monitor.raw_url || (typeof monitor.url === 'string' ? monitor.url : '');
    return raw.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
}

function getMonitorDisplayName(monitor: Incident['monitor']): string {
    if (monitor.display_name) return monitor.display_name;
    const clean = getMonitorCleanDomain(monitor);
    return clean || `Monitor #${monitor.id}`;
}

function formatDuration(minutes: number | null, startedAt: string, endedAt: string | null): string {
    if (minutes !== null && minutes !== undefined) {
        if (minutes < 1) return '< 1m';
        if (minutes < 60) return `${minutes}m`;
        const hrs = Math.floor(minutes / 60);
        const rem = minutes % 60;
        return rem > 0 ? `${hrs}h ${rem}m` : `${hrs}h`;
    }

    if (startedAt && !endedAt) {
        const start = new Date(startedAt).getTime();
        const now = Date.now();
        const diffMins = Math.max(0, Math.floor((now - start) / 60000));
        if (diffMins < 60) return `${diffMins}m ongoing`;
        const hrs = Math.floor(diffMins / 60);
        const rem = diffMins % 60;
        return `${hrs}h ${rem}m ongoing`;
    }

    return '—';
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function handleKeyDown(e: KeyboardEvent) {
    if (
        e.key === '/' &&
        document.activeElement !== searchInputRef.value &&
        (document.activeElement as HTMLElement)?.tagName !== 'INPUT' &&
        (document.activeElement as HTMLElement)?.tagName !== 'TEXTAREA'
    ) {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

const statusTabs = computed(() => [
    { key: 'all', label: 'All Incidents' },
    { key: 'ongoing', label: 'Ongoing Outages', count: props.stats.ongoing_count },
    { key: 'resolved', label: 'Resolved' },
]);

const rangeOptions = [
    { value: '7d', label: 'Past 7 Days' },
    { value: '30d', label: 'Past 30 Days' },
    { value: '90d', label: 'Past 90 Days' },
    { value: 'all', label: 'All Time' },
];
</script>

<template>
    <PublicLayout
        :title="pageTitle"
        :description="pageDescription"
        :og-image="ogImage"
        :canonical-url="shareUrl"
        :share-url="shareUrl"
        :share-text="shareText"
        :show-server-stats="true"
        :json-ld="jsonLd"
    >
        <!-- Top Status & Summary Strip -->
        <div
            class="mb-5 overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-xs sm:rounded-3xl dark:border-gray-800/80 dark:bg-gray-900/90"
        >
            <div class="grid grid-cols-2 divide-y divide-gray-100 sm:grid-cols-4 sm:divide-x sm:divide-y-0 dark:divide-gray-800/80">
                <!-- System Operational Status -->
                <div class="flex flex-col items-center justify-center p-4 text-center">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span
                                v-if="stats.ongoing_count > 0"
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-3 w-3 rounded-full"
                                :class="stats.ongoing_count > 0 ? 'bg-rose-500' : 'bg-emerald-500'"
                            ></span>
                        </span>
                        <span
                            class="text-base font-black tracking-tight sm:text-lg"
                            :class="stats.ongoing_count > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'"
                        >
                            {{ stats.ongoing_count > 0 ? `${stats.ongoing_count} Active Outage` : 'All Systems Up' }}
                        </span>
                    </div>
                    <span class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400"> Current System State </span>
                </div>

                <!-- 30d Resolved -->
                <div class="flex flex-col items-center justify-center p-4 text-center">
                    <div class="flex items-center gap-1.5">
                        <Icon name="checkCircle" class="h-4 w-4 text-emerald-500" />
                        <span class="text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                            {{ stats.resolved_30d }}
                        </span>
                    </div>
                    <span class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400"> Resolved (Past 30d) </span>
                </div>

                <!-- MTTR (Mean Time to Recovery) -->
                <div class="flex flex-col items-center justify-center p-4 text-center">
                    <div class="flex items-center gap-1.5">
                        <Icon name="clock" class="h-4 w-4 text-amber-500" />
                        <span class="text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                            {{ stats.avg_duration_minutes > 0 ? `${stats.avg_duration_minutes}m` : '< 1m' }}
                        </span>
                    </div>
                    <span class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400"> Avg Recovery Time (MTTR) </span>
                </div>

                <!-- Public Services Tracked -->
                <div class="flex flex-col items-center justify-center p-4 text-center">
                    <div class="flex items-center gap-1.5">
                        <Icon name="globe" class="h-4 w-4 text-blue-500" />
                        <span class="text-xl font-black tracking-tight text-gray-900 sm:text-2xl dark:text-white">
                            {{ stats.total_public_monitors }}
                        </span>
                    </div>
                    <span class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400"> Monitored Services </span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="mb-4 rounded-2xl border border-gray-200/80 bg-white p-1.5 shadow-xs sm:rounded-full dark:border-gray-800/80 dark:bg-gray-900/90">
            <div class="flex flex-col gap-2 md:flex-row md:items-center">
                <!-- Search Input -->
                <div class="relative flex min-w-[200px] flex-1 items-center">
                    <label for="search-incidents" class="sr-only">Search incidents</label>
                    <Icon name="search" class="pointer-events-none absolute left-3.5 h-3.5 w-3.5 text-gray-400" />
                    <input
                        ref="searchInputRef"
                        id="search-incidents"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search incidents by service or URL..."
                        class="w-full rounded-full border-0 bg-transparent py-1.5 pr-9 pl-9 text-xs text-gray-900 placeholder-gray-400 focus:ring-0 focus:outline-none dark:text-white dark:placeholder-gray-500"
                        @input="debounceSearch"
                    />
                    <div class="absolute right-2.5 flex items-center">
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="
                                searchQuery = '';
                                applyFilters();
                            "
                            class="cursor-pointer rounded-full p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            aria-label="Clear search"
                        >
                            <Icon name="x" class="h-3 w-3" />
                        </button>
                        <kbd
                            v-else
                            class="py-0.2 hidden items-center rounded border border-gray-200 bg-gray-100 px-1.5 font-mono text-[9px] text-gray-400 sm:inline-flex dark:border-gray-700 dark:bg-gray-800"
                        >
                            /
                        </kbd>
                    </div>
                </div>

                <!-- Status Filter Tabs -->
                <div class="flex items-center gap-1">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.key"
                        type="button"
                        @click="filterByStatus(tab.key)"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="
                            statusFilter === tab.key
                                ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                        "
                    >
                        <span>{{ tab.label }}</span>
                        <span
                            v-if="tab.count !== undefined && tab.count > 0"
                            class="py-0.2 rounded-full bg-rose-500 px-1.5 text-[9px] font-extrabold text-white"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- Time Range Select -->
                <div class="relative inline-flex items-center">
                    <label for="range-filter" class="sr-only">Time range</label>
                    <select
                        id="range-filter"
                        v-model="rangeFilter"
                        @change="applyFilters"
                        class="cursor-pointer appearance-none rounded-full bg-gray-100/90 py-1.5 pr-7 pl-3 text-xs font-medium text-gray-700 focus:outline-none dark:bg-gray-800/90 dark:text-gray-200"
                    >
                        <option v-for="r in rangeOptions" :key="r.value" :value="r.value">
                            {{ r.label }}
                        </option>
                    </select>
                    <Icon name="chevronDown" class="pointer-events-none absolute right-2.5 h-3 w-3 text-gray-500" />
                </div>
            </div>
        </div>

        <!-- Active Filter Indicator & Count Bar -->
        <div class="mb-4 flex items-center justify-between px-2 text-xs font-medium text-gray-500 dark:text-gray-400">
            <span> Showing {{ incidents.total }} {{ incidents.total === 1 ? 'incident' : 'incidents' }} </span>
            <button
                v-if="hasActiveFilters"
                type="button"
                @click="resetFilters"
                class="cursor-pointer text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
            >
                Reset filters
            </button>
        </div>

        <!-- Incidents List -->
        <div v-if="incidents.data.length > 0" class="space-y-3">
            <div
                v-for="inc in incidents.data"
                :key="inc.id"
                class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-4 shadow-xs transition-all hover:border-gray-300 sm:p-5 dark:border-gray-800/80 dark:bg-gray-900/90 dark:hover:border-gray-700"
            >
                <div class="flex flex-col justify-between gap-3 border-b border-gray-100 pb-3 sm:flex-row sm:items-center dark:border-gray-800/70">
                    <!-- Left: Service Identity -->
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                            :class="
                                inc.ended_at
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400'
                                    : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400'
                            "
                        >
                            <Icon :name="inc.ended_at ? 'checkCircle' : 'alertTriangle'" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <a
                                    :href="`/m/${getMonitorCleanDomain(inc.monitor)}`"
                                    class="text-sm font-bold text-gray-900 transition-colors hover:text-blue-600 sm:text-base dark:text-white dark:hover:text-blue-400"
                                >
                                    {{ getMonitorDisplayName(inc.monitor) }}
                                </a>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="
                                        inc.ended_at
                                            ? 'border border-emerald-200/60 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'animate-pulse border border-rose-200/60 bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                                    "
                                >
                                    {{ inc.ended_at ? 'Resolved' : 'Ongoing Outage' }}
                                </span>
                            </div>
                            <span class="block truncate font-mono text-xs text-gray-400 dark:text-gray-500">
                                {{ inc.monitor.raw_url || (typeof inc.monitor.url === 'string' ? inc.monitor.url : '') }}
                            </span>
                        </div>
                    </div>

                    <!-- Right: Outage Duration -->
                    <div class="flex items-center gap-2 self-start text-xs sm:self-auto">
                        <span class="font-medium text-gray-400 dark:text-gray-500">Duration:</span>
                        <span
                            class="rounded-lg px-2.5 py-1 font-mono font-bold"
                            :class="
                                inc.ended_at
                                    ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'
                            "
                        >
                            {{ formatDuration(inc.duration_minutes, inc.started_at, inc.ended_at) }}
                        </span>
                    </div>
                </div>

                <!-- Outage Details and Timestamps -->
                <div class="flex flex-col justify-between gap-3 pt-3 text-xs sm:flex-row sm:items-center">
                    <!-- Reason / Status -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400">Trigger:</span>
                        <span
                            v-if="inc.status_code"
                            class="rounded bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            HTTP {{ inc.status_code }}
                        </span>
                        <span v-if="inc.reason" class="font-medium text-gray-600 dark:text-gray-300">
                            {{ inc.reason }}
                        </span>
                        <span v-else-if="!inc.status_code" class="text-gray-400 dark:text-gray-500"> Service unavailable / Check failed </span>
                        <span
                            v-if="inc.response_time"
                            class="rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                            {{ inc.response_time }}ms latency
                        </span>
                    </div>

                    <!-- Time info -->
                    <div class="flex items-center gap-3 text-gray-400 dark:text-gray-500">
                        <span>Started: {{ formatDate(inc.started_at) }}</span>
                        <span v-if="inc.ended_at">Resolved: {{ formatRelativeTime(inc.ended_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Pagination Bar -->
            <div v-if="incidents.last_page > 1" class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                <span class="text-xs text-gray-500 dark:text-gray-400"> Page {{ incidents.current_page }} of {{ incidents.last_page }} </span>
                <div class="flex items-center gap-1.5">
                    <button
                        v-for="(link, idx) in incidents.links"
                        :key="idx"
                        type="button"
                        :disabled="!link.url"
                        @click="link.url && router.visit(link.url, { preserveState: true })"
                        class="cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                        :class="
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="rounded-3xl border border-dashed border-gray-200 bg-white/50 py-16 text-center backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/50"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
            >
                <Icon name="checkCircle" class="h-8 w-8" />
            </div>
            <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">
                {{ hasActiveFilters ? 'No matching incidents found' : '100% Operational Reliability' }}
            </h3>
            <p class="mx-auto max-w-sm text-sm text-gray-500 dark:text-gray-400">
                {{
                    hasActiveFilters
                        ? 'Try changing or clearing your search filters to find historical incident records.'
                        : 'No downtime incidents have been recorded in this timeframe across any public monitors.'
                }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    class="cursor-pointer rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    Clear all filters
                </button>
                <a v-else href="/public-monitors" class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700">
                    Browse All Monitors
                </a>
            </div>
        </div>
    </PublicLayout>
</template>
