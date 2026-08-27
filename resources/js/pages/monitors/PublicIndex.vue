<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorCardPublic from '@/components/MonitorCardPublic.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { formatChecksCount, formatDuration, formatRelativeTime } from '@/composables/useMonitorHelpers';
import { useMonitorStatusStream } from '@/composables/useMonitorStatusStream';
import { globalToasts } from '@/composables/useToastNotifications';
import type { Monitor } from '@/types/monitor';
import { Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Paginator<T> { data: T[]; links: any; meta: { current_page: number; from: number; last_page: number; per_page: number; to: number; total: number } }
interface Incident { id: number; monitor_id: number; type: string; started_at: string; ended_at: string | null; duration_minutes: number | null; reason: string | null; status_code: number | null; monitor: { id: number; url: string; name: string | null; is_public: boolean; raw_url: string } }
interface Props {
    monitors: Paginator<Monitor>; filters: { search: string | null; status_filter: string; tag_filter: string | null; sort_by: string };
    stats: { total: number; up: number; down: number; total_public: number; daily_checks?: number; monthly_checks?: number };
    availableTags?: Array<{ id: number; name: { en: string } }>; latestIncidents?: Incident[]; showSmolLaunchBadge?: boolean; appUrl: string;
}
const props = defineProps<Props>();

const pageTitle = computed(() => `${props.stats.total_public} Public Monitors - Uptime Kita`);
const pageDescription = computed(() => `Monitor ${props.stats.total_public} websites in real-time. ${props.stats.up} operational, ${props.stats.down} down. Free open-source uptime monitoring.`);
const shareUrl = computed(() => `${props.appUrl}/public-monitors`);
const shareText = computed(() => `Check out ${props.stats.total_public} public monitors on Uptime Kita! ${props.stats.up} services operational.`);
const ogImage = computed(() => `${props.appUrl}/og/monitors.png`);
const jsonLd = computed(() => ({
    '@context': 'https://schema.org', '@type': 'WebSite', name: 'Uptime Kita - Public Monitors',
    url: shareUrl.value, description: pageDescription.value,
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
    { value: 'default', label: 'Default (by ID)' }, { value: 'popular', label: 'Most Popular' },
    { value: 'uptime', label: 'Best Uptime' }, { value: 'response_time', label: 'Fastest Response' },
    { value: 'newest', label: 'Newest First' }, { value: 'name', label: 'Name (A-Z)' }, { value: 'status', label: 'Status (Down First)' },
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
const filterByStatus = (s: string) => { statusFilter.value = statusFilter.value === s ? 'all' : s; applyFilters(); };

let activeReq: AbortController | null = null;
let loadingMoreActive = false;
const loadMore = async () => {
    if (isLoading.value) return;
    if (activeReq) activeReq.abort();
    isLoading.value = true; loadingMoreActive = true;
    const nextPage = currentPage.value + 1;
    activeReq = new AbortController();
    const p = new URLSearchParams(); p.append('page', String(nextPage));
    if (searchQuery.value) p.append('search', searchQuery.value);
    if (statusFilter.value !== 'all') p.append('status_filter', statusFilter.value);
    if (tagFilter.value) p.append('tag_filter', tagFilter.value);
    if (sortBy.value !== 'default') p.append('sort_by', sortBy.value);
    try {
        const res = await fetch(`/public-monitors?${p.toString()}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: activeReq.signal });
        if (!res.ok) throw new Error(String(res.status));
        const data = await res.json();
        if (!activeReq.signal.aborted) {
            monitorsData.value.push(...data.data);
            monitorsMeta.value = cleanMeta(data.meta);
            currentPage.value = nextPage;
            await nextTick(); loadingMoreActive = false;
        }
    } catch (e) { if (!(e instanceof Error && e.name === 'AbortError')) console.error(e); loadingMoreActive = false; }
    finally { isLoading.value = false; activeReq = null; }
};

const viewMonitor = (m: Monitor) => { router.visit(`/m/${m.url.replace(/^https?:\/\//, '')}`); };
const viewIncidentMonitor = (inc: Incident) => { router.visit(`/m/${inc.monitor.raw_url.replace(/^https?:\/\//, '')}`); };

let isInitialSetup = true;
watch(() => props.monitors, (nm) => {
    if (loadingMoreActive) { monitorsMeta.value = cleanMeta(nm.meta); return; }
    if (isInitialSetup || cleanMeta(nm.meta).current_page === 1) { monitorsData.value = nm.data || []; currentPage.value = cleanMeta(nm.meta).current_page; isInitialSetup = false; }
    monitorsMeta.value = cleanMeta(nm.meta);
}, { deep: true });
watch(() => props.filters, (nf) => {
    searchQuery.value = nf.search || ''; statusFilter.value = nf.status_filter; tagFilter.value = nf.tag_filter || ''; sortBy.value = nf.sort_by || 'default'; isInitialSetup = true;
}, { deep: true });

const showBackToTop = ref(false);
const onScroll = () => { showBackToTop.value = window.scrollY > 300; };
onMounted(() => window.addEventListener('scroll', onScroll));
onUnmounted(() => window.removeEventListener('scroll', onScroll));

const hasActiveFilter = computed(() => !!searchQuery.value || statusFilter.value !== 'all' || !!tagFilter.value || sortBy.value !== 'default');

// Free domain checker — hero
const domainInput = ref('');
const domainChecking = ref(false);
const domainResult = ref<null | { url: string; host: string; status_code: number | null; ok: boolean; response_time_ms: number; error?: string }>(null);
const domainError = ref('');
const exampleDomains = ['example.com', 'google.com', 'github.com'];
async function checkDomain() {
    const v = domainInput.value.trim();
    if (!v) { domainError.value = 'Enter a domain or URL.'; return; }
    domainChecking.value = true; domainResult.value = null; domainError.value = '';
    try {
        const res = await fetch(`/api/check-domain?url=${encodeURIComponent(v)}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!res.ok) { domainError.value = data.message || 'Check failed.'; return; }
        domainResult.value = data;
    } catch { domainError.value = 'Network error. Try again.'; }
    finally { domainChecking.value = false; }
}
function tryExample(domain: string) { domainInput.value = domain; checkDomain(); }
function monitorThisDomain() {
    if (!domainResult.value) return;
    router.visit(`/monitor/create?url=${encodeURIComponent('https://' + domainResult.value.host)}`);
}
</script>

<template>
    <PublicLayout :title="pageTitle" :description="pageDescription" :og-image="ogImage" :canonical-url="shareUrl" :share-url="shareUrl" :share-text="shareText" :show-server-stats="true" :json-ld="jsonLd">

        <!-- Hero: Free Website Checker -->
        <div class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-600 to-indigo-700 p-6 sm:p-8 shadow-lg">
            <div class="mx-auto max-w-3xl text-center">
                <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></span> Free • No signup • Instant
                </div>
                <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Check any website in seconds</h2>
                <p class="mt-2 text-sm text-blue-100 sm:text-base">Enter a domain or URL to see if it's up — response time & status, instantly.</p>
                <form @submit.prevent="checkDomain" class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label for="hero-domain-input" class="sr-only">Domain or URL to check</label>
                    <input id="hero-domain-input" v-model="domainInput" type="text" placeholder="example.com or https://example.com" autocomplete="off" class="flex-1 rounded-xl border-0 bg-white px-5 py-3.5 text-base text-gray-900 placeholder-gray-400 shadow-sm focus:ring-2 focus:ring-white/50" />
                    <button type="submit" :disabled="domainChecking" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-blue-700 shadow-sm hover:bg-blue-50 disabled:opacity-60">
                        <Icon v-if="domainChecking" name="loader" class="h-4 w-4 animate-spin" />
                        <Icon v-else name="search" class="h-4 w-4" />
                        {{ domainChecking ? 'Checking…' : 'Check Now' }}
                    </button>
                </form>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs">
                    <span class="text-blue-200">Try:</span>
                    <button v-for="ex in exampleDomains" :key="ex" type="button" @click="tryExample(ex)" class="rounded-full bg-white/15 px-3 py-1 font-medium text-white hover:bg-white/25 backdrop-blur">{{ ex }}</button>
                </div>
                <p v-if="domainError" role="alert" class="mt-3 rounded-lg bg-red-500/20 px-3 py-2 text-sm text-white">{{ domainError }}</p>
                <div v-if="domainResult" role="status" aria-live="polite" class="mt-4 flex flex-wrap items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm shadow-sm">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full" :class="domainResult.ok ? 'bg-green-500' : 'bg-red-500'"></span>
                    <span class="font-semibold" :class="domainResult.ok ? 'text-green-700' : 'text-red-700'">{{ domainResult.ok ? 'Up' : 'Down / Error' }}</span>
                    <span class="font-medium text-gray-700">{{ domainResult.host }}</span>
                    <span v-if="domainResult.status_code" class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs">{{ domainResult.status_code }}</span>
                    <span class="text-gray-500">{{ domainResult.response_time_ms }} ms</span>
                    <button v-if="domainResult.ok" type="button" @click="monitorThisDomain" class="ml-1 rounded-full bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700">Monitor this →</button>
                    <span v-if="domainResult.error" class="w-full text-xs text-red-600">{{ domainResult.error }}</span>
                </div>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="mb-6 flex flex-wrap items-center justify-center gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800 sm:gap-6">
            <button type="button" @click="filterByStatus('all')" class="flex items-center gap-2" :class="statusFilter === 'all' ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-500'"><span class="text-lg font-bold">{{ stats.total_public }}</span> Total</button>
            <span class="hidden h-4 w-px bg-gray-200 dark:bg-gray-700 sm:block"></span>
            <button type="button" @click="filterByStatus('up')" class="flex items-center gap-1.5" :class="statusFilter === 'up' ? 'font-semibold text-green-700 dark:text-green-400' : 'text-gray-500'"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ stats.up }} Operational</button>
            <span class="hidden h-4 w-px bg-gray-200 dark:bg-gray-700 sm:block"></span>
            <span class="flex items-center gap-1.5 text-gray-500"><span class="font-bold text-blue-600 dark:text-blue-400">{{ Math.round((stats.up / stats.total_public) * 100) || 0 }}%</span> Uptime</span>
            <span class="hidden h-4 w-px bg-gray-200 dark:bg-gray-700 sm:block"></span>
            <span class="flex items-center gap-1.5 text-gray-500" :title="`${(stats.daily_checks || 0).toLocaleString('id-ID')} checks last 24h`"><Icon name="activity" class="h-3.5 w-3.5" />{{ formatChecksCount(stats.daily_checks || 0) }} / 24h</span>
        </div>

        <!-- Filters -->
        <Card class="mb-6 p-2"><CardContent class="p-4">
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="flex-1"><label for="search-monitors" class="sr-only">Search monitors</label><input id="search-monitors" v-model="searchQuery" type="text" placeholder="Search monitors..." class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2" @input="debounceSearch" /></div>
                <div class="grid grid-cols-3 gap-2">
                    <div><label for="sort-by" class="sr-only">Sort by</label><select id="sort-by" v-model="sortBy" @change="applyFilters" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2"><option v-for="o in sortOptions" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
                    <div><label for="status-filter" class="sr-only">Filter by status</label><select id="status-filter" v-model="statusFilter" @change="applyFilters" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2"><option value="all">All Status</option><option value="up">Operational</option><option value="down">Down</option></select></div>
                    <div><label for="tag-filter" class="sr-only">Filter by tag</label><select id="tag-filter" v-model="tagFilter" @change="applyFilters" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2"><option value="">All Tags</option><option v-for="tag in props.availableTags" :key="tag.id" :value="tag.name.en">{{ tag.name.en }}</option></select></div>
                </div>
                <div><button @click="router.visit('/monitor/create')" class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700 sm:w-auto sm:py-2"><Icon name="plus" class="h-4 w-4" /><span>Create Monitor</span></button></div>
            </div>
        </CardContent></Card>

        <!-- Skeletons -->
        <div v-if="isLoading && monitorsData.length === 0" class="grid grid-cols-2 gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
            <Card v-for="i in 8" :key="i" class="p-4"><Skeleton class="mb-3 h-5 w-3/4" /><Skeleton class="mb-2 h-4 w-full" /><Skeleton class="h-4 w-1/2" /></Card>
        </div>

        <!-- Empty -->
        <div v-else-if="monitorsData.length === 0" class="py-12 text-center">
            <Icon name="search" class="mx-auto mb-4 h-16 w-16 text-gray-400" />
            <h2 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">{{ hasActiveFilter ? 'No results for current filters' : 'No monitors found' }}</h2>
            <p class="text-gray-500 dark:text-gray-400">{{ hasActiveFilter ? 'Try adjusting search or filters' : 'No public monitors yet' }}</p>
            <button v-if="hasActiveFilter" @click="searchQuery = ''; statusFilter = 'all'; tagFilter = ''; sortBy = 'default'; applyFilters()" class="mt-4 text-sm text-blue-600 hover:underline">Clear filters</button>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
            <MonitorCardPublic v-for="m in monitorsData" :key="m.id" :monitor="m" @click="viewMonitor" />
        </div>

        <!-- Load More -->
        <div v-if="currentPage < monitorsMeta.last_page" class="mt-8 text-center">
            <button @click="loadMore" :disabled="isLoading" class="inline-flex w-full items-center justify-center rounded-lg bg-gray-600 px-6 py-4 text-sm font-medium text-white hover:bg-gray-700 disabled:bg-gray-400 sm:w-auto sm:py-3">
                <Icon v-if="isLoading" name="loader" class="mr-2 h-4 w-4 animate-spin" /><span v-else>Load More Monitors</span>
            </button>
        </div>

        <!-- Demo Banner (secondary, below grid) -->
        <Link href="/status/demo" class="mt-8 flex items-center justify-between rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 hover:border-blue-300 hover:shadow-md dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20">
            <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50"><Icon name="activity" class="h-5 w-5 text-blue-600 dark:text-blue-400" /></div><div><p class="font-medium text-gray-900 dark:text-white">Try our Demo Status Page</p><p class="text-sm text-gray-600 dark:text-gray-400">See how status pages work</p></div></div>
            <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400"><span class="hidden text-sm font-medium sm:inline">View Demo</span><Icon name="arrowRight" class="h-5 w-5" /></div>
        </Link>

        <!-- Latest Incidents -->
        <div v-if="props.latestIncidents?.length" class="mt-8">
            <Card class="max-h-[50vh] overflow-y-auto"><CardContent class="px-4 sm:px-6">
                <div class="mb-4 flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white">Latest Incidents</h2><span class="text-sm text-gray-500">Last 10</span></div>
                <div class="space-y-3">
                    <div v-for="inc in props.latestIncidents" :key="inc.id" class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50" @click="viewIncidentMonitor(inc)">
                        <div :class="['flex h-8 w-8 items-center justify-center rounded-full', inc.ended_at ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30']"><Icon :name="inc.ended_at ? 'checkCircle' : 'alertCircle'" :class="['h-4 w-4', inc.ended_at ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400']" /></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ inc.monitor.raw_url }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><span v-if="inc.type">Type: {{ inc.type }} • </span><span v-if="inc.status_code">Status: {{ inc.status_code }} • </span>Started {{ formatRelativeTime(inc.started_at) }}</p>
                            <p v-if="inc.reason" class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ inc.reason }}</p>
                            <p v-if="inc.duration_minutes" class="mt-1 text-xs text-gray-500">Duration: {{ formatDuration(inc.duration_minutes) }}</p>
                        </div>
                        <span :class="['rounded-full px-2 py-1 text-xs font-medium', inc.ended_at ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300']">{{ inc.ended_at ? 'Resolved' : 'Ongoing' }}</span>
                    </div>
                </div>
            </CardContent></Card>
        </div>

        <div v-if="props.showSmolLaunchBadge" class="mt-12 flex justify-center pb-8"><a href="https://smollaunch.com" target="_blank" rel="noopener"><img src="https://smollaunch.com/badges/featured.svg" alt="Featured on Smol Launch" loading="lazy" width="250" height="60" /></a></div>

        <button v-show="showBackToTop" @click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="fixed bottom-6 right-6 z-50 rounded-full bg-blue-600 p-3 text-white shadow-lg hover:bg-blue-700 dark:bg-blue-500" aria-label="Back to top"><Icon name="chevronUp" class="h-5 w-5" /></button>
    </PublicLayout>
</template>
