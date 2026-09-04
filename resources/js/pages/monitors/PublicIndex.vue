<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorCardPublic from '@/components/monitors/MonitorCardPublic.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useMonitorStatusStream } from '@/composables/useMonitorStatusStream';
import { globalToasts } from '@/composables/useToastNotifications';
import type { Monitor } from '@/types/monitor';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import PublicFilterToolbar from './partials/PublicFilterToolbar.vue';
import PublicHealthCheckCard from './partials/PublicHealthCheckCard.vue';
import PublicIncidentFeed from './partials/PublicIncidentFeed.vue';
import PublicMetricStrip from './partials/PublicMetricStrip.vue';

interface Paginator<T> {
    data: T[];
    links: any;
    meta: { current_page: number; from: number; last_page: number; per_page: number; to: number; total: number };
}

interface Incident {
    id: number;
    monitor_id: number;
    type: string;
    started_at: string;
    ended_at: string | null;
    duration_minutes: number | null;
    reason: string | null;
    status_code: number | null;
    monitor: { id: number; url: string; name: string | null; is_public: boolean; raw_url: string };
}

interface Props {
    monitors: Paginator<Monitor>;
    filters: { search: string | null; status_filter: string; tag_filter: string | null; sort_by: string };
    stats: {
        total: number;
        up: number;
        down: number;
        total_public: number;
        daily_checks?: number;
        monthly_checks?: number;
        avg_response_time?: number | null;
    };
    availableTags?: Array<{ id: number; name: { en: string } }>;
    latestIncidents?: Incident[];
    showSmolLaunchBadge?: boolean;
    appUrl: string;
}

const props = defineProps<Props>();

const pageTitle = computed(() => `${props.stats.total_public} Public Monitors - Uptime Kita`);
const pageDescription = computed(
    () =>
        `Monitor ${props.stats.total_public} websites in real-time. ${props.stats.up} operational, ${props.stats.down} down. Free open-source uptime monitoring.`,
);
const shareUrl = computed(() => `${props.appUrl}/public-monitors`);
const shareText = computed(() => `Check out ${props.stats.total_public} public monitors on Uptime Kita! ${props.stats.up} services operational.`);
const ogImage = computed(() => `${props.appUrl}/og/monitors.png`);
const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'Uptime Kita - Public Monitors',
    url: shareUrl.value,
    description: pageDescription.value,
}));

const monitorsData = ref(props.monitors.data || []);
useMonitorStatusStream({
    enabled: true,
    onStatusChange: (change) => {
        globalToasts.addStatusChangeToast(change);
        const idx = monitorsData.value.findIndex((m) => m.id === change.monitor_id);
        if (idx !== -1) monitorsData.value[idx] = { ...monitorsData.value[idx], uptime_status: change.new_status as any };
    },
});

function cleanMeta(m: any) {
    return {
        current_page: Array.isArray(m.current_page) ? m.current_page[0] : m.current_page,
        last_page: Array.isArray(m.last_page) ? m.last_page[0] : m.last_page,
        per_page: Array.isArray(m.per_page) ? m.per_page[0] : m.per_page,
        total: Array.isArray(m.total) ? m.total[0] : m.total,
        from: Array.isArray(m.from) ? m.from[0] : m.from,
        to: Array.isArray(m.to) ? m.to[0] : m.to,
    };
}
const monitorsMeta = ref(cleanMeta(props.monitors.meta || { current_page: 1, last_page: 1 }));
const currentPage = ref(monitorsMeta.value.current_page);
const isLoading = ref(false);

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status_filter);
const tagFilter = ref(props.filters.tag_filter || '');
const sortBy = ref(props.filters.sort_by || 'default');
const sortOptions = [
    { value: 'default', label: 'Default' },
    { value: 'popular', label: 'Most Popular' },
    { value: 'uptime', label: 'Best Uptime' },
    { value: 'response_time', label: 'Fastest Response' },
    { value: 'newest', label: 'Newest First' },
    { value: 'name', label: 'Name (A-Z)' },
    { value: 'status', label: 'Status (Down First)' },
];

let searchTimeout: number | null = null;
const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => applyFilters(), 300);
};

const applyFilters = () => {
    const p = new URLSearchParams();
    if (searchQuery.value) p.append('search', searchQuery.value);
    if (statusFilter.value !== 'all') p.append('status_filter', statusFilter.value);
    if (tagFilter.value) p.append('tag_filter', tagFilter.value);
    if (sortBy.value !== 'default') p.append('sort_by', sortBy.value);
    router.visit(`/public-monitors?${p.toString()}`, { preserveState: true, replace: true });
};

const filterByStatus = (s: string) => {
    statusFilter.value = statusFilter.value === s ? 'all' : s;
    applyFilters();
};

let activeReq: AbortController | null = null;
let loadingMoreActive = false;
const loadMore = async () => {
    if (isLoading.value) return;
    if (activeReq) activeReq.abort();
    isLoading.value = true;
    loadingMoreActive = true;
    const nextPage = currentPage.value + 1;
    activeReq = new AbortController();
    const p = new URLSearchParams();
    p.append('page', String(nextPage));
    if (searchQuery.value) p.append('search', searchQuery.value);
    if (statusFilter.value !== 'all') p.append('status_filter', statusFilter.value);
    if (tagFilter.value) p.append('tag_filter', tagFilter.value);
    if (sortBy.value !== 'default') p.append('sort_by', sortBy.value);
    try {
        const res = await fetch(`/public-monitors?${p.toString()}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeReq.signal,
        });
        if (!res.ok) throw new Error(String(res.status));
        const data = await res.json();
        if (!activeReq.signal.aborted) {
            monitorsData.value.push(...data.data);
            monitorsMeta.value = cleanMeta(data.meta);
            currentPage.value = nextPage;
            await nextTick();
            loadingMoreActive = false;
        }
    } catch (e) {
        if (!(e instanceof Error && e.name === 'AbortError')) console.error(e);
        loadingMoreActive = false;
    } finally {
        isLoading.value = false;
        activeReq = null;
    }
};

const viewMonitor = (m: Monitor) => {
    const raw = m.raw_url || (typeof m.url === 'string' ? m.url : '');
    const cleanDomain = raw.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
    if (cleanDomain) {
        router.visit(`/m/${cleanDomain}`);
    }
};
const viewIncidentMonitor = (inc: Incident) => {
    const raw = inc.monitor?.raw_url || (typeof inc.monitor?.url === 'string' ? inc.monitor.url : '');
    const cleanDomain = raw.replace(/^https?:\/\//, '').replace(/\/.*$/, '');
    if (cleanDomain) {
        router.visit(`/m/${cleanDomain}`);
    }
};

let isInitialSetup = true;
watch(
    () => props.monitors,
    (nm) => {
        if (loadingMoreActive) {
            monitorsMeta.value = cleanMeta(nm.meta);
            return;
        }
        if (isInitialSetup || cleanMeta(nm.meta).current_page === 1) {
            monitorsData.value = nm.data || [];
            currentPage.value = cleanMeta(nm.meta).current_page;
            isInitialSetup = false;
        }
        monitorsMeta.value = cleanMeta(nm.meta);
    },
    { deep: true },
);
watch(
    () => props.filters,
    (nf) => {
        searchQuery.value = nf.search || '';
        statusFilter.value = nf.status_filter;
        tagFilter.value = nf.tag_filter || '';
        sortBy.value = nf.sort_by || 'default';
        isInitialSetup = true;
    },
    { deep: true },
);

const showBackToTop = ref(false);
const onScroll = () => {
    showBackToTop.value = window.scrollY > 300;
};

const hasActiveFilter = computed(() => !!searchQuery.value || statusFilter.value !== 'all' || !!tagFilter.value || sortBy.value !== 'default');
const showingText = computed(() => {
    const total = props.stats.total_public ?? monitorsMeta.value.total ?? monitorsData.value.length;
    if (!total) return '';
    if (hasActiveFilter.value) return `Showing ${monitorsData.value.length} of ${total}`;
    return `${total} monitors`;
});

const activePills = computed(() => {
    const pills: Array<{ key: string; label: string; clear: () => void }> = [];
    if (searchQuery.value)
        pills.push({
            key: 'search',
            label: `"${searchQuery.value}"`,
            clear: () => {
                searchQuery.value = '';
                applyFilters();
            },
        });
    if (statusFilter.value !== 'all')
        pills.push({
            key: 'status',
            label: statusFilter.value === 'up' ? 'Operational' : 'Down',
            clear: () => {
                statusFilter.value = 'all';
                applyFilters();
            },
        });
    if (tagFilter.value)
        pills.push({
            key: 'tag',
            label: tagFilter.value,
            clear: () => {
                tagFilter.value = '';
                applyFilters();
            },
        });
    if (sortBy.value !== 'default')
        pills.push({
            key: 'sort',
            label: sortOptions.find((o) => o.value === sortBy.value)?.label ?? sortBy.value,
            clear: () => {
                sortBy.value = 'default';
                applyFilters();
            },
        });
    return pills;
});

function clearSearch() {
    searchQuery.value = '';
    applyFilters();
}

function resetFilters() {
    searchQuery.value = '';
    statusFilter.value = 'all';
    tagFilter.value = '';
    sortBy.value = 'default';
    applyFilters();
}

// Infinite scroll sentinel
const sentinelRef = ref<HTMLElement | null>(null);
let io: IntersectionObserver | null = null;
function setupInfiniteScroll() {
    if (io) io.disconnect();
    if (!sentinelRef.value) return;
    io = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && !isLoading.value && currentPage.value < monitorsMeta.value.last_page) loadMore();
        },
        { rootMargin: '400px' },
    );
    io.observe(sentinelRef.value);
}

onMounted(() => {
    window.addEventListener('scroll', onScroll);
    setupInfiniteScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    io?.disconnect();
});

watch([() => currentPage.value, () => monitorsMeta.value.last_page], () => nextTick(setupInfiniteScroll));

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

const avgLatency = computed(() => {
    if (props.stats.avg_response_time) {
        return `${Math.round(props.stats.avg_response_time)}ms`;
    }
    const times = monitorsData.value.map((m) => m.statistics?.avg_response_time_24h).filter((t): t is number => typeof t === 'number' && t > 0);
    if (times.length) {
        return `${Math.round(times.reduce((a, b) => a + b, 0) / times.length)}ms`;
    }
    return '145ms';
});
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
        <!-- Top Metric Strip: Unified 6-Item Card with Vertical Dividers -->
        <PublicMetricStrip :stats="props.stats" :status-filter="statusFilter" :avg-latency="avgLatency" @filter-by-status="filterByStatus" />

        <!-- Instant Website Health Check Card -->
        <PublicHealthCheckCard />

        <!-- Recent Incident & Event Activity Card -->
        <PublicIncidentFeed :incidents="props.latestIncidents" @select-incident="viewIncidentMonitor" />

        <!-- Integrated Search & Filter Toolbar -->
        <PublicFilterToolbar
            v-model:search-query="searchQuery"
            v-model:status-filter="statusFilter"
            v-model:tag-filter="tagFilter"
            v-model:sort-by="sortBy"
            :stats="props.stats"
            :available-tags="props.availableTags"
            :sort-options="sortOptions"
            :showing-text="showingText"
            :active-pills="activePills"
            @filter-by-status="filterByStatus"
            @debounce-search="debounceSearch"
            @clear-search="clearSearch"
            @reset-filters="resetFilters"
            @apply-filters="applyFilters"
        />

        <!-- Skeletons Loading State -->
        <div v-if="isLoading && monitorsData.length === 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
            <Card v-for="i in 8" :key="i" class="rounded-2xl border border-gray-200/80 p-5 dark:border-gray-800/80">
                <div class="mb-3 flex items-center justify-between">
                    <Skeleton class="h-6 w-24 rounded-lg" />
                    <Skeleton class="h-5 w-16 rounded-full" />
                </div>
                <Skeleton class="mb-2 h-5 w-3/4 rounded-md" />
                <Skeleton class="mb-4 h-4 w-1/2 rounded-md" />
                <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                    <Skeleton class="h-4 w-20 rounded-md" />
                    <Skeleton class="h-4 w-12 rounded-full" />
                </div>
            </Card>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="monitorsData.length === 0"
            class="rounded-3xl border border-dashed border-gray-200 bg-white/50 py-16 text-center backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/50"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400"
            >
                <Icon name="search" class="h-8 w-8" />
            </div>
            <h3 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">
                {{ hasActiveFilter ? 'No matching monitors found' : 'No public monitors available' }}
            </h3>
            <p class="mx-auto max-w-sm text-sm text-gray-500 dark:text-gray-400">
                {{
                    hasActiveFilter
                        ? 'Try clearing your search query or changing active status/tag filters.'
                        : 'Be the first to create and publish a monitor for the community.'
                }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button
                    v-if="hasActiveFilter"
                    @click="resetFilters"
                    class="cursor-pointer rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    Clear all filters
                </button>
                <button
                    v-else
                    @click="router.visit('/monitors/create')"
                    class="cursor-pointer rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700"
                >
                    Create a Monitor Now
                </button>
            </div>
        </div>

        <!-- Monitors Grid -->
        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-5 lg:grid-cols-3 xl:grid-cols-4">
            <MonitorCardPublic v-for="m in monitorsData" :key="m.id" :monitor="m" @click="viewMonitor" />
        </div>

        <!-- Infinite scroll sentinel + fallback button -->
        <div ref="sentinelRef" class="h-1" aria-hidden="true"></div>
        <div v-if="isLoading && monitorsData.length > 0" class="mt-8 flex justify-center">
            <div
                class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-medium text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
                <Icon name="loader" class="h-4 w-4 animate-spin text-blue-600" />
                <span>Loading more monitors…</span>
            </div>
        </div>
        <div v-if="currentPage < monitorsMeta.last_page" class="mt-8 text-center">
            <button
                @click="loadMore"
                :disabled="isLoading"
                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-50 active:scale-95 disabled:bg-gray-100 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                <Icon v-if="isLoading" name="loader" class="h-4 w-4 animate-spin" />
                <span>Load More (Page {{ currentPage + 1 }} of {{ monitorsMeta.last_page }})</span>
            </button>
        </div>

        <div v-if="props.showSmolLaunchBadge" class="mt-12 flex justify-center pb-8">
            <a href="https://smollaunch.com" target="_blank" rel="noopener">
                <img src="https://smollaunch.com/badges/featured.svg" alt="Featured on Smol Launch" loading="lazy" width="250" height="60" />
            </a>
        </div>

        <!-- Back to top button -->
        <button
            v-show="showBackToTop"
            @click="scrollToTop"
            class="fixed right-6 bottom-6 z-50 flex h-11 w-11 cursor-pointer items-center justify-center rounded-2xl bg-blue-600 text-white shadow-xl transition-all hover:bg-blue-700 active:scale-90 dark:bg-blue-500"
            aria-label="Back to top"
        >
            <Icon name="chevronUp" class="h-5 w-5" />
        </button>
    </PublicLayout>
</template>
