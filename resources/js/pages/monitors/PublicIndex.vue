<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorCardPublic from '@/components/MonitorCardPublic.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDuration, formatRelativeTime } from '@/composables/useMonitorHelpers';
import { useMonitorStatusStream } from '@/composables/useMonitorStatusStream';
import { globalToasts } from '@/composables/useToastNotifications';
import type { Monitor } from '@/types/monitor';
import { Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

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
    stats: { total: number; up: number; down: number; total_public: number; daily_checks?: number; monthly_checks?: number };
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
    router.visit(`/m/${m.url.replace(/^https?:\/\//, '')}`);
};
const viewIncidentMonitor = (inc: Incident) => {
    router.visit(`/m/${inc.monitor.raw_url.replace(/^https?:\/\//, '')}`);
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
    const total = monitorsMeta.value.total ?? monitorsData.value.length;
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

const searchInputRef = ref<HTMLInputElement | null>(null);
function handleKeyDown(e: KeyboardEvent) {
    if (
        e.key === '/' &&
        document.activeElement !== searchInputRef.value &&
        document.activeElement !== heroInputRef.value &&
        (document.activeElement as HTMLElement)?.tagName !== 'INPUT' &&
        (document.activeElement as HTMLElement)?.tagName !== 'TEXTAREA'
    ) {
        e.preventDefault();
        searchInputRef.value?.focus();
    }
}

onMounted(() => {
    window.addEventListener('scroll', onScroll);
    window.addEventListener('keydown', handleKeyDown);
    setupInfiniteScroll();
});
onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('keydown', handleKeyDown);
    io?.disconnect();
});
watch([() => currentPage.value, () => monitorsMeta.value.last_page], () => nextTick(setupInfiniteScroll));

// Free domain checker — hero
const domainInput = ref('');
const domainChecking = ref(false);
const domainResult = ref<null | { url: string; host: string; status_code: number | null; ok: boolean; response_time_ms: number; error?: string }>(
    null,
);
const domainError = ref('');
const exampleDomains = ['google.com', 'github.com', 'cloudflare.com', 'laravel.com'];
const showApiSnippet = ref(false);
const copiedApi = ref(false);

async function checkDomain() {
    const v = domainInput.value.trim();
    if (!v) {
        domainError.value = 'Enter a domain or URL to inspect.';
        return;
    }
    domainChecking.value = true;
    domainResult.value = null;
    domainError.value = '';
    try {
        const res = await fetch(`/api/check-domain?url=${encodeURIComponent(v)}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!res.ok) {
            domainError.value = data.message || 'Check failed. Please check the domain.';
            return;
        }
        domainResult.value = data;
    } catch {
        domainError.value = 'Network error. Please try again.';
    } finally {
        domainChecking.value = false;
    }
}
function tryExample(domain: string) {
    domainInput.value = domain;
    checkDomain();
}
function monitorThisDomain() {
    if (!domainResult.value) return;
    router.visit(`/monitor/create?url=${encodeURIComponent('https://' + domainResult.value.host)}`);
}

function copyApiCommand(customHost?: string) {
    const target = customHost || domainInput.value.trim() || 'example.com';
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://uptime.syofyanzuhad.dev';
    const cmd = `curl -X GET "${baseUrl}/api/v1/check?url=${encodeURIComponent(target)}"`;
    navigator.clipboard.writeText(cmd);
    copiedApi.value = true;
    setTimeout(() => {
        copiedApi.value = false;
    }, 2000);
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function formatCompactNumber(num?: number): string {
    if (!num) return '0';
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 1_000) return (num / 1_000).toFixed(1).replace(/\.0$/, '') + 'K';
    return num.toLocaleString();
}

// Phase 3: incidents collapsible + hero focus + mobile compact mode
const incidentsExpanded = ref(false);
const showMobileChecker = ref(false);
const heroInputRef = ref<HTMLInputElement | null>(null);
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
        <!-- Mobile: Ultra-Compact 1-Tap Health Check Banner -->
        <div class="sm:hidden mb-2.5">
            <button
                v-if="!showMobileChecker"
                type="button"
                @click="showMobileChecker = true"
                class="flex w-full items-center justify-between rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 px-3 py-2 text-left text-xs font-bold text-white shadow-xs"
            >
                <div class="flex items-center gap-2">
                    <Icon name="zap" class="h-3.5 w-3.5 text-amber-300" />
                    <span>Instant Website Health Check</span>
                </div>
                <span class="rounded bg-white/20 px-1.5 py-0.5 text-[9px] font-extrabold uppercase">Free Test</span>
            </button>

            <div
                v-else
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 p-3 shadow-md"
            >
                <div class="mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-white">
                        <Icon name="zap" class="h-3.5 w-3.5 text-amber-300" />
                        <span>Instant Website Health Check</span>
                    </div>
                    <button
                        type="button"
                        @click="showMobileChecker = false"
                        class="rounded-lg p-1 text-white/70 hover:bg-white/10 hover:text-white"
                        aria-label="Close"
                    >
                        <Icon name="x" class="h-3.5 w-3.5" />
                    </button>
                </div>

                <form @submit.prevent="checkDomain" class="flex gap-1.5">
                    <input
                        v-model="domainInput"
                        type="text"
                        placeholder="Enter domain or URL..."
                        class="w-full rounded-xl border-0 bg-white py-1.5 px-3 text-xs text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                    />
                    <button
                        type="submit"
                        :disabled="domainChecking"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-blue-700 shadow-xs active:scale-95 disabled:opacity-60"
                    >
                        <Icon v-if="domainChecking" name="loader" class="h-3 w-3 animate-spin" />
                        <span v-else>Check</span>
                    </button>
                </form>

                <div v-if="domainResult" class="mt-2 rounded-xl bg-black/40 p-2 text-xs text-white">
                    <div class="flex items-center justify-between">
                        <span class="font-bold">{{ domainResult.domain }}</span>
                        <span
                            class="rounded px-1.5 py-0.2 text-[9px] font-extrabold uppercase"
                            :class="domainResult.status === 'up' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
                        >
                            {{ domainResult.status === 'up' ? 'Online' : 'Down' }} ({{ domainResult.status_code || 'Err' }})
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop Hero: Sleek & Compact Website Health Checker -->
        <div
            class="relative mb-4 hidden overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-slate-900 p-3.5 shadow-md ring-1 ring-white/10 sm:block sm:p-4.5"
        >
            <!-- Ambient Glow Accents -->
            <div class="pointer-events-none absolute -top-12 -left-12 h-36 w-36 rounded-full bg-blue-400/20 blur-2xl" />
            <div class="pointer-events-none absolute -right-12 -bottom-12 h-36 w-36 rounded-full bg-indigo-500/20 blur-2xl" />

            <div class="relative mx-auto max-w-3xl">
                <div class="mb-2 flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-white/10 text-white backdrop-blur-sm">
                            <Icon name="zap" class="h-3.5 w-3.5 text-amber-300" />
                        </div>
                        <h2 class="text-sm font-extrabold text-white sm:text-base">Instant Website Health Check</h2>
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 self-start rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold text-blue-100 backdrop-blur-sm sm:self-auto"
                    >
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                        <span>Free & No Sign-up</span>
                    </span>
                </div>

                <!-- Compact Search Input Form -->
                <form @submit.prevent="checkDomain" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label for="hero-domain-input" class="sr-only">Domain or URL to check</label>
                    <div class="relative flex-1">
                        <Icon name="globe" class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                        <input
                            ref="heroInputRef"
                            id="hero-domain-input"
                            v-model="domainInput"
                            type="text"
                            placeholder="Enter any domain or URL (e.g. google.com, myapp.io)..."
                            autocomplete="off"
                            aria-label="Domain or URL to check"
                            class="w-full rounded-xl border-0 bg-white/95 py-2 pr-8 pl-9 text-xs text-gray-900 placeholder-gray-400 shadow-inner backdrop-blur-sm transition-all focus:bg-white focus:ring-2 focus:ring-white/80 focus:outline-none sm:text-sm"
                            @keydown.escape="
                                domainResult = null;
                                domainError = '';
                            "
                        />
                        <button
                            v-if="domainInput"
                            type="button"
                            @click="
                                domainInput = '';
                                domainResult = null;
                                domainError = '';
                            "
                            class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                        >
                            <Icon name="x" class="h-3 w-3" />
                        </button>
                    </div>

                    <button
                        type="submit"
                        :disabled="domainChecking"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-white px-4 py-2 text-xs font-bold text-blue-700 shadow-sm transition-all hover:bg-blue-50 active:scale-95 disabled:opacity-70"
                    >
                        <Icon v-if="domainChecking" name="loader" class="h-3 w-3 animate-spin" />
                        <Icon v-else name="zap" class="h-3 w-3 text-blue-600" />
                        <span>{{ domainChecking ? 'Checking…' : 'Check Now' }}</span>
                    </button>
                </form>

                <!-- Compact Example Chips & API Toggle -->
                <div class="mt-2 flex flex-wrap items-center justify-between gap-1.5 text-[11px] text-blue-100/80">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-medium text-[10px]">Try:</span>
                        <button
                            v-for="ex in exampleDomains"
                            :key="ex"
                            type="button"
                            @click="tryExample(ex)"
                            class="rounded border border-white/10 bg-white/10 px-1.5 py-0.5 text-[10px] font-medium text-white transition-colors hover:bg-white/20 active:scale-95"
                        >
                            {{ ex }}
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="showApiSnippet = !showApiSnippet"
                        class="inline-flex items-center gap-1 rounded border border-white/20 bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-white transition-all hover:bg-white/20 active:scale-95"
                    >
                        <Icon name="terminal" class="h-2.5 w-2.5 text-emerald-300" />
                        <span>{{ showApiSnippet ? 'Hide API' : 'CLI / API' }}</span>
                        <span class="rounded bg-emerald-400/30 px-1 text-[8px] font-extrabold text-emerald-200 uppercase">v1</span>
                    </button>
                </div>

                <!-- Interactive API Code Drawer -->
                <div
                    v-if="showApiSnippet"
                    class="mt-2.5 overflow-hidden rounded-xl border border-white/15 bg-gray-950/90 p-2.5 text-left shadow-lg backdrop-blur-md"
                >
                    <div class="mb-1.5 flex items-center justify-between border-b border-white/10 pb-1.5 text-[10px] text-gray-400">
                        <div class="flex items-center gap-1.5 font-sans font-bold text-white">
                            <Icon name="code" class="h-3 w-3 text-emerald-400" />
                            <span>Instant Uptime API</span>
                            <span class="rounded bg-emerald-500/20 px-1 py-0.2 text-[9px] font-extrabold text-emerald-300">30 req/min free</span>
                        </div>
                        <span class="font-mono text-[9px] text-gray-400">GET /api/v1/check</span>
                    </div>
                    <div
                        class="flex items-center justify-between gap-2 overflow-x-auto rounded-lg bg-black/60 p-2 font-mono text-[11px] text-emerald-400"
                    >
                        <span class="truncate text-gray-300 select-all">
                            curl -X GET "https://uptime.syofyanzuhad.dev/api/v1/check?url={{ domainInput || 'example.com' }}"
                        </span>
                        <button
                            type="button"
                            @click="copyApiCommand(domainInput || 'example.com')"
                            class="shrink-0 rounded bg-white/10 px-2 py-0.5 text-[10px] font-bold text-white transition-colors hover:bg-white/20 active:scale-95"
                        >
                            {{ copiedApi ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>
                </div>

                <!-- Live Checker Result Banner -->
                <div
                    v-if="domainResult"
                    class="mt-2.5 rounded-xl p-2.5 text-left shadow-sm backdrop-blur-sm transition-all"
                    :class="
                        domainResult.status === 'up'
                            ? 'border border-emerald-400/40 bg-emerald-950/60 text-emerald-100'
                            : 'border border-rose-400/40 bg-rose-950/60 text-rose-100'
                    "
                >
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg font-bold text-white"
                                :class="domainResult.status === 'up' ? 'bg-emerald-500' : 'bg-rose-500'"
                            >
                                <Icon :name="domainResult.status === 'up' ? 'check' : 'x'" class="h-4 w-4" />
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold">{{ domainResult.domain }}</span>
                                    <span
                                        class="rounded-full px-1.5 py-0.2 text-[9px] font-extrabold uppercase"
                                        :class="domainResult.status === 'up' ? 'bg-emerald-500/30 text-emerald-300' : 'bg-rose-500/30 text-rose-300'"
                                    >
                                        {{ domainResult.status === 'up' ? 'Operational' : 'Down' }}
                                    </span>
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[10px] text-white/70">
                                    <span v-if="domainResult.status_code">HTTP {{ domainResult.status_code }}</span>
                                    <span v-if="domainResult.response_time">Latency: {{ domainResult.response_time }}ms</span>
                                    <span v-if="domainResult.ip">IP: {{ domainResult.ip }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 self-end sm:self-auto">
                            <Link
                                v-if="domainResult.monitor_slug"
                                :href="`/m/${domainResult.monitor_slug}`"
                                class="rounded-lg bg-white/20 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-white/30"
                            >
                                View Details
                            </Link>
                            <Link
                                v-else
                                :href="`/monitor/create?url=${encodeURIComponent(domainResult.url || 'https://' + domainResult.domain)}`"
                                class="rounded-lg bg-white px-2.5 py-1 text-[11px] font-bold text-blue-700 hover:bg-blue-50"
                            >
                                Track Uptime
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile: Sleek Micro Horizontal Metric Ribbon -->
        <div class="sm:hidden flex overflow-x-auto no-scrollbar gap-1.5 pb-1 mb-2.5">
            <button
                v-for="s in [
                    { key: 'all', label: 'Total', value: stats.total_public, color: 'text-gray-900 dark:text-white', activeClass: 'border-blue-500 bg-blue-50/80 dark:bg-blue-950/40' },
                    { key: 'up', label: 'Online', value: stats.up, color: 'text-emerald-600 dark:text-emerald-400', activeClass: 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/40' },
                    { key: 'down', label: 'Down', value: stats.down, color: stats.down > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500', activeClass: 'border-rose-500 bg-rose-50/80 dark:bg-rose-950/40' },
                    { key: 'checks', label: '24h Pings', value: formatCompactNumber(stats.daily_checks || 0), color: 'text-indigo-600 dark:text-indigo-400', activeClass: '' },
                    { key: 'overall', label: 'Uptime', value: Math.round((stats.up / (stats.total_public || 1)) * 100) + '%', color: 'text-blue-600 dark:text-blue-400', activeClass: '' },
                ]"
                :key="s.key"
                type="button"
                @click="s.key === 'all' || s.key === 'up' || s.key === 'down' ? filterByStatus(s.key) : null"
                class="shrink-0 flex items-center gap-1.5 rounded-xl border border-gray-200/80 bg-white/90 px-2.5 py-1.5 text-xs shadow-2xs dark:border-gray-800/80 dark:bg-gray-900/90"
                :class="statusFilter === s.key ? s.activeClass : ''"
            >
                <span class="text-[10px] font-bold text-gray-500 uppercase dark:text-gray-400">{{ s.label }}:</span>
                <span class="font-extrabold" :class="s.color">{{ s.value }}</span>
            </button>
        </div>

        <!-- Desktop Metric Summary Cards: Compact 5-Column Grid -->
        <div class="mb-4 hidden grid-cols-2 gap-2 sm:grid sm:grid-cols-3 lg:grid-cols-5 sm:gap-3">
            <button
                v-for="s in [
                    {
                        key: 'all',
                        label: 'Monitors Tracked',
                        value: formatCompactNumber(stats.total_public),
                        color: 'text-gray-900 dark:text-white',
                        activeClass: 'border-blue-500/50 bg-blue-50/50 dark:bg-blue-950/20',
                    },
                    {
                        key: 'up',
                        label: 'Operational',
                        value: formatCompactNumber(stats.up),
                        color: 'text-emerald-600 dark:text-emerald-400',
                        activeClass: 'border-emerald-500/50 bg-emerald-50/50 dark:bg-emerald-950/20',
                    },
                    {
                        key: 'down',
                        label: 'Active Incidents',
                        value: String(stats.down),
                        color: stats.down > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400',
                        activeClass: 'border-rose-500/50 bg-rose-50/50 dark:bg-rose-950/20',
                    },
                    {
                        key: 'checks',
                        label: '24h Pings Checked',
                        value: formatCompactNumber(stats.daily_checks || 0),
                        color: 'text-indigo-600 dark:text-indigo-400',
                        activeClass: '',
                    },
                    {
                        key: 'overall',
                        label: 'Network Uptime',
                        value: Math.round((stats.up / (stats.total_public || 1)) * 100) + '%',
                        color: 'text-blue-600 dark:text-blue-400',
                        activeClass: '',
                    },
                ]"
                :key="s.key"
                type="button"
                @click="s.key === 'all' || s.key === 'up' || s.key === 'down' ? filterByStatus(s.key) : null"
                class="group flex flex-col justify-between rounded-2xl border border-gray-200/80 bg-white/80 p-2.5 text-left shadow-xs backdrop-blur-sm transition-all hover:border-gray-300 hover:shadow-sm sm:p-3 dark:border-gray-800/80 dark:bg-gray-900/80 dark:hover:border-gray-700"
                :class="statusFilter === s.key ? s.activeClass : ''"
            >
                <span class="text-[10px] font-bold tracking-wider text-gray-500 uppercase sm:text-[11px] dark:text-gray-400">{{ s.label }}</span>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-lg font-black tracking-tight sm:text-xl" :class="s.color">
                        {{ s.value }}
                    </span>
                </div>
            </button>
        </div>

        <!-- Desktop Featured Quick Action Links: Sleek Inline Strip -->
        <div class="mb-4 hidden sm:grid sm:grid-cols-3 sm:gap-3">
            <Link
                href="/status/demo"
                class="group flex items-center justify-between rounded-2xl border border-blue-200/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/40 to-white px-3.5 py-2.5 shadow-xs transition-all hover:border-blue-400 hover:shadow-sm dark:border-blue-900/50 dark:from-blue-950/30 dark:via-indigo-950/20 dark:to-gray-900"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow-xs transition-transform group-hover:scale-105"
                    >
                        <Icon name="activity" class="h-3.5 w-3.5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h3 class="truncate text-xs font-bold text-gray-900 dark:text-white">Status Page</h3>
                            <span class="rounded bg-blue-600/10 px-1 py-0.2 text-[8px] font-extrabold text-blue-600 uppercase dark:bg-blue-400/10 dark:text-blue-400">Demo</span>
                        </div>
                        <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">90-day component health</p>
                    </div>
                </div>
                <Icon name="arrowRight" class="h-3.5 w-3.5 shrink-0 text-blue-600 transition-transform group-hover:translate-x-0.5 dark:text-blue-400" />
            </Link>

            <Link
                href="/monitors"
                class="group flex items-center justify-between rounded-2xl border border-purple-200/80 bg-gradient-to-r from-purple-50/80 via-pink-50/40 to-white px-3.5 py-2.5 shadow-xs transition-all hover:border-purple-400 hover:shadow-sm dark:border-purple-900/50 dark:from-purple-950/30 dark:via-purple-950/20 dark:to-gray-900"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-purple-600 text-white shadow-xs transition-transform group-hover:scale-105"
                    >
                        <Icon name="layoutGrid" class="h-3.5 w-3.5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h3 class="truncate text-xs font-bold text-gray-900 dark:text-white">NOC Wallboard</h3>
                            <span class="rounded bg-purple-600/10 px-1 py-0.2 text-[8px] font-extrabold text-purple-600 uppercase dark:bg-purple-400/10 dark:text-purple-400">Kiosk</span>
                        </div>
                        <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">High-density live grid</p>
                    </div>
                </div>
                <Icon
                    name="arrowRight"
                    class="h-3.5 w-3.5 shrink-0 text-purple-600 transition-transform group-hover:translate-x-0.5 dark:text-purple-400"
                />
            </Link>

            <!-- Developer API Spotlight Card with 1-Click Copy -->
            <button
                type="button"
                @click="copyApiCommand('example.com')"
                class="group flex cursor-pointer items-center justify-between rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50/80 via-teal-50/40 to-white px-3.5 py-2.5 text-left shadow-xs transition-all hover:border-emerald-400 hover:shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/30 dark:via-teal-950/20 dark:to-gray-900"
            >
                <div class="flex min-w-0 items-center gap-2.5">
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-xs transition-transform group-hover:scale-105"
                    >
                        <Icon name="terminal" class="h-3.5 w-3.5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1">
                            <h3 class="truncate text-xs font-bold text-gray-900 dark:text-white">Health Check API</h3>
                            <span class="rounded bg-emerald-600/10 px-1 py-0.2 text-[8px] font-extrabold text-emerald-600 uppercase dark:bg-emerald-400/10 dark:text-emerald-400">Free</span>
                        </div>
                        <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">cURL / CI/CD automation</p>
                    </div>
                </div>
                <div class="ml-2 flex shrink-0 items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                    <span class="hidden sm:inline text-[10px]">{{ copiedApi ? 'Copied!' : 'Copy' }}</span>
                    <Icon :name="copiedApi ? 'check' : 'copy'" class="h-3 w-3" />
                </div>
            </button>
        </div>

        <!-- Recent Incidents Alert Strip (if active) -->
        <div v-if="props.latestIncidents?.length" class="mb-4">
            <Card class="rounded-2xl border border-gray-200/80 bg-white/80 shadow-xs backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80">
                <CardContent class="p-3 sm:p-3.5">
                    <button type="button" @click="incidentsExpanded = !incidentsExpanded" class="flex w-full items-center justify-between text-left">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-lg"
                                :class="stats.down > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 animate-pulse' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400'"
                            >
                                <Icon :name="stats.down > 0 ? 'alertTriangle' : 'activity'" class="h-3.5 w-3.5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h2 class="text-xs font-bold text-gray-900 sm:text-sm dark:text-white">Recent Incident & Event Activity</h2>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-1.5 py-0.2 text-[9px] font-bold text-emerald-600 dark:text-emerald-400">
                                        <span class="h-1 w-1 rounded-full bg-emerald-500 animate-ping"></span>
                                        Live Feed
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-gray-500">
                            <span>{{ incidentsExpanded ? 'Collapse' : 'Expand' }} ({{ props.latestIncidents.length }})</span>
                            <Icon :name="incidentsExpanded ? 'chevronUp' : 'chevronDown'" class="h-3.5 w-3.5" />
                        </span>
                    </button>

                    <div v-if="incidentsExpanded" class="mt-2.5 space-y-1.5 border-t border-gray-100 pt-2.5 dark:border-gray-800">
                        <div
                            v-for="inc in props.latestIncidents.slice(0, 4)"
                            :key="inc.id"
                            class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-gray-100 bg-gray-50/50 p-2.5 transition-colors hover:bg-gray-100/80 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:bg-gray-800/80"
                            @click="viewIncidentMonitor(inc)"
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <div
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg"
                                    :class="
                                        inc.ended_at
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                            : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'
                                    "
                                >
                                    <Icon :name="inc.ended_at ? 'checkCircle' : 'alertCircle'" class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-xs font-bold text-gray-900 dark:text-white">{{ inc.monitor.raw_url }}</p>
                                        <span v-if="inc.status_code" class="font-mono text-[10px] text-gray-400">HTTP {{ inc.status_code }}</span>
                                    </div>
                                    <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">
                                        <span>{{ formatRelativeTime(inc.started_at) }}</span>
                                        <span v-if="inc.duration_minutes"> • {{ formatDuration(inc.duration_minutes) }}</span>
                                        <span v-if="inc.reason" class="ml-1 text-gray-400">({{ inc.reason }})</span>
                                    </p>
                                </div>
                            </div>

                            <span
                                class="shrink-0 rounded-full px-2 py-0.2 text-[10px] font-bold"
                                :class="
                                    inc.ended_at
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                "
                            >
                                {{ inc.ended_at ? 'Resolved' : 'Ongoing' }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filter & Search Toolbar: Sleek Compact Bar -->
        <div
            class="mb-4 rounded-2xl border border-gray-200/80 bg-white/80 p-3 shadow-xs backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
        >
            <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center">
                <!-- Search Input with Keyboard Shortcut Indicator -->
                <div class="relative flex-1">
                    <label for="search-monitors" class="sr-only">Search monitors</label>
                    <Icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                    <input
                        ref="searchInputRef"
                        id="search-monitors"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search public monitors (e.g. google, api, blog)..."
                        class="w-full rounded-xl border border-gray-200/80 bg-gray-50/50 py-1.5 pr-14 pl-9 text-xs text-gray-900 placeholder-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-white dark:focus:bg-gray-800"
                        @input="debounceSearch"
                    />
                    <div class="absolute top-1/2 right-2 flex -translate-y-1/2 items-center gap-1">
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="clearSearch"
                            class="rounded-full p-0.5 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700"
                            aria-label="Clear search"
                        >
                            <Icon name="x" class="h-3 w-3" />
                        </button>
                        <kbd
                            v-else
                            class="hidden items-center rounded border border-gray-200 bg-gray-100 px-1 font-mono text-[9px] text-gray-500 sm:inline-flex dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        >
                            /
                        </kbd>
                    </div>
                </div>

                <!-- Status Segmented Pills -->
                <div class="flex items-center rounded-lg bg-gray-100/80 p-0.5 dark:bg-gray-800/80">
                    <button
                        type="button"
                        @click="filterByStatus('all')"
                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition-all"
                        :class="
                            statusFilter === 'all'
                                ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400'
                        "
                    >
                        All ({{ stats.total_public }})
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('up')"
                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition-all"
                        :class="
                            statusFilter === 'up'
                                ? 'bg-white text-emerald-600 shadow-xs dark:bg-gray-700 dark:text-emerald-400'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400'
                        "
                    >
                        Online ({{ stats.up }})
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('down')"
                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition-all"
                        :class="
                            statusFilter === 'down'
                                ? 'bg-white text-rose-600 shadow-xs dark:bg-gray-700 dark:text-rose-400'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400'
                        "
                    >
                        Down ({{ stats.down }})
                    </button>
                </div>

                <!-- Dropdowns: Sort and Tags -->
                <div class="flex items-center gap-1.5">
                    <label for="sort-by" class="sr-only">Sort by</label>
                    <select
                        id="sort-by"
                        v-model="sortBy"
                        @change="applyFilters"
                        class="rounded-xl border border-gray-200/80 bg-gray-50/50 px-2.5 py-1.5 text-xs font-semibold text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:bg-gray-800"
                    >
                        <option v-for="o in sortOptions" :key="o.value" :value="o.value">Sort: {{ o.label }}</option>
                    </select>

                    <label for="tag-filter" class="sr-only">Filter by tag</label>
                    <select
                        id="tag-filter"
                        v-model="tagFilter"
                        @change="applyFilters"
                        class="rounded-xl border border-gray-200/80 bg-gray-50/50 px-2.5 py-1.5 text-xs font-semibold text-gray-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-gray-700/80 dark:bg-gray-800/50 dark:text-gray-200 dark:focus:bg-gray-800"
                    >
                        <option value="">All Tags</option>
                        <option v-for="tag in props.availableTags" :key="tag.id" :value="tag.name.en">#{{ tag.name.en }}</option>
                    </select>

                    <button
                        @click="router.visit('/monitor/create')"
                        class="inline-flex shrink-0 items-center justify-center gap-1 rounded-xl bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-95"
                    >
                        <Icon name="plus" class="h-3 w-3" />
                        <span>Add</span>
                    </button>
                </div>
            </div>

            <!-- Active Pills Row -->
            <div
                v-if="showingText || activePills.length"
                class="mt-2 flex flex-wrap items-center gap-1.5 border-t border-gray-100 pt-2 text-[11px] dark:border-gray-800"
            >
                <span v-if="showingText" class="font-medium text-gray-500 dark:text-gray-400">{{ showingText }}</span>
                <span v-if="showingText && activePills.length" class="text-gray-300 dark:text-gray-700">•</span>
                <span
                    v-for="pill in activePills"
                    :key="pill.key"
                    class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 font-semibold text-blue-700 ring-1 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300"
                >
                    {{ pill.label }}
                    <button
                        type="button"
                        @click="pill.clear()"
                        class="rounded-full p-0.5 hover:bg-blue-200 dark:hover:bg-blue-800"
                        :aria-label="`Remove ${pill.key} filter`"
                    >
                        <Icon name="x" class="h-2.5 w-2.5" />
                    </button>
                </span>
                <button
                    v-if="activePills.length"
                    type="button"
                    @click="
                        searchQuery = '';
                        statusFilter = 'all';
                        tagFilter = '';
                        sortBy = 'default';
                        applyFilters();
                    "
                    class="font-semibold text-blue-600 hover:underline dark:text-blue-400"
                >
                    Reset all
                </button>
            </div>
        </div>

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
                    @click="
                        searchQuery = '';
                        statusFilter = 'all';
                        tagFilter = '';
                        sortBy = 'default';
                        applyFilters();
                    "
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    Clear all filters
                </button>
                <button
                    v-else
                    @click="router.visit('/monitor/create')"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700"
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
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-xs font-bold text-gray-700 shadow-sm hover:bg-gray-50 active:scale-95 disabled:bg-gray-100 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
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
            class="fixed right-6 bottom-6 z-50 flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-xl transition-all hover:bg-blue-700 active:scale-90 dark:bg-blue-500"
            aria-label="Back to top"
        >
            <Icon name="chevronUp" class="h-5 w-5" />
        </button>
    </PublicLayout>
</template>
