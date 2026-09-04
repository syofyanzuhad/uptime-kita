<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useBookmarks } from '@/composables/useBookmarks';
import { usePollMode } from '@/composables/usePollMode';
import type { SharedData } from '@/types';
import type { Monitor } from '@/types/monitor';
import { usePage } from '@inertiajs/vue3';
import { Bookmark, ChevronDown, RefreshCw, Search } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MonitorCard from './MonitorCard.vue';
import Button from '@/components/ui/button/Button.vue';

interface Props {
    searchQuery?: string;
    statusFilter?: 'all' | 'up' | 'down' | 'unsubscribed' | 'globally_enabled' | 'globally_disabled';
    allCount?: number;
    onlineCount?: number;
    offlineCount?: number;
    unsubscribedCount?: number;
    disabledCount?: number;
    enabledCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    searchQuery: '',
    statusFilter: 'all',
    allCount: 0,
    onlineCount: 0,
    offlineCount: 0,
    unsubscribedCount: 0,
    disabledCount: 0,
    enabledCount: 0,
});

const pinnedMonitors = ref<Monitor[]>([]);
const loading = ref(true);
const isPolling = ref(false);
const error = ref<string | null>(null);

// Toggle active state
const togglingMonitors = ref<Set<number>>(new Set());
const loadingMonitors = ref<Set<number>>(new Set());

// Pagination state
const currentPage = ref(1);
const hasMorePages = ref(false);
const loadingMore = ref(false);
const totalMonitors = ref(0);
const showingFrom = ref(0);
const showingTo = ref(0);

const page = usePage<SharedData>();
const { togglePin, onPinChanged } = useBookmarks();

const refreshIconClass = computed(() => {
    return loading.value || isPolling.value ? 'animate-spin' : '';
});

const filteredMonitors = computed(() => {
    if (!pinnedMonitors.value || pinnedMonitors.value.length === 0) {
        return [];
    }
    let monitors = pinnedMonitors.value;
    // Filter by status
    if (props.statusFilter === 'up' || props.statusFilter === 'down') {
        monitors = monitors.filter((monitor) => monitor.uptime_status === props.statusFilter);
    } else if (props.statusFilter === 'globally_enabled') {
        monitors = monitors.filter((monitor) => monitor.uptime_check_enabled);
    } else if (props.statusFilter === 'globally_disabled') {
        monitors = monitors.filter((monitor) => !monitor.uptime_check_enabled);
    }
    // Filter by search query
    if (props.searchQuery && props.searchQuery.trim().length >= 3) {
        const query = props.searchQuery.toLowerCase().trim();
        monitors = monitors.filter((monitor) => {
            const domain = getDomainFromUrl(monitor.url).toLowerCase();
            const url = monitor.url.toLowerCase();
            return domain.includes(query) || url.includes(query);
        });
    }
    return monitors;
});

const getDomainFromUrl = (url: string) => {
    try {
        const domain = new URL(url).hostname;
        return domain.replace('www.', '');
    } catch {
        return url;
    }
};

async function fetchPinnedMonitors(isInitialLoad = false, pageNum = 1) {
    try {
        if (isInitialLoad) {
            loading.value = true;
            currentPage.value = 1;
        } else if (pageNum > 1) {
            loadingMore.value = true;
        } else {
            isPolling.value = true;
        }

        const params = new URLSearchParams();
        params.append('page', String(pageNum));
        if (props.searchQuery && props.searchQuery.trim().length >= 3) {
            params.append('search', props.searchQuery.trim());
        }
        if (props.statusFilter !== 'all') {
            params.append('status_filter', props.statusFilter);
        }

        const response = await fetch(`/pinned-monitors?${params.toString()}`);
        if (!response.ok) {
            throw new Error('Failed to fetch pinned monitors');
        }

        const result = await response.json();

        if (isInitialLoad || pageNum === 1) {
            pinnedMonitors.value = result.data || [];
        } else {
            pinnedMonitors.value = [...pinnedMonitors.value, ...(result.data || [])];
        }

        hasMorePages.value = result.meta?.current_page < result.meta?.last_page;
        totalMonitors.value = result.meta?.total || 0;
        showingFrom.value = result.meta?.from || 0;
        showingTo.value = result.meta?.to || 0;
        currentPage.value = result.meta?.current_page || 1;

        error.value = null;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loading.value = false;
        isPolling.value = false;
        loadingMore.value = false;
    }
}

async function loadMore() {
    if (hasMorePages.value && !loadingMore.value) {
        await fetchPinnedMonitors(false, currentPage.value + 1);
    }
}

async function toggleMonitorActive(monitorId: number) {
    if (togglingMonitors.value.has(monitorId)) return;

    togglingMonitors.value.add(monitorId);

    try {
        const response = await fetch(`/monitors/${monitorId}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (page.props as any).csrf_token,
            },
        });

        if (!response.ok) throw new Error('Failed to toggle monitor status');

        const result = await response.json();

        const monitor = pinnedMonitors.value.find((m) => m.id === monitorId);
        if (monitor) {
            monitor.is_subscribed = result.is_active;
        }
    } catch (err) {
        console.error('Error toggling monitor active:', err);
    } finally {
        togglingMonitors.value.delete(monitorId);
    }
}

async function handleTogglePin(monitorId: number) {
    if (loadingMonitors.value.has(monitorId)) return;

    loadingMonitors.value.add(monitorId);

    try {
        await togglePin(monitorId);
    } catch (err) {
        console.error('Error toggling pin:', err);
    } finally {
        loadingMonitors.value.delete(monitorId);
    }
}

// Watch for prop changes
watch(
    [() => props.searchQuery, () => props.statusFilter],
    ([newQuery, newFilter], [oldQuery, oldFilter]) => {
        if (newQuery !== oldQuery || newFilter !== oldFilter) {
            currentPage.value = 1;
            hasMorePages.value = false;
            showingFrom.value = 0;
            showingTo.value = 0;
            totalMonitors.value = 0;
        }

        if (newQuery.trim().length === 0 || newQuery.trim().length >= 3) {
            fetchPinnedMonitors(true, 1);
        }
    },
    { deep: true },
);

const { isAutoPolling } = usePollMode();

let pollingInterval: number | null = null;
let cleanupPinCallback: (() => void) | null = null;

function startPolling() {
    if (!isAutoPolling.value || pollingInterval) return;
    pollingInterval = window.setInterval(() => {
        if (!loading.value && !loadingMore.value) {
            fetchPinnedMonitors(false, 1);
        }
    }, 60000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

onMounted(() => {
    fetchPinnedMonitors(true);
    startPolling();

    cleanupPinCallback = onPinChanged(() => {
        fetchPinnedMonitors(false, 1);
    });
});

onUnmounted(() => {
    stopPolling();
    if (cleanupPinCallback) {
        cleanupPinCallback();
    }
});
</script>

<template>
    <Card
        class="overflow-hidden rounded-3xl border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900/80"
    >
        <CardHeader class="border-b border-gray-100 pb-3 dark:border-gray-800">
            <CardTitle class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20">
                        <Bookmark class="h-4 w-4 fill-amber-500/30" />
                    </div>
                    <div>
                        <span class="text-base font-bold text-gray-900 dark:text-white">Pinned Monitors</span>
                        <span
                            v-if="!loading && pinnedMonitors.length > 0"
                            class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                        >
                            {{ filteredMonitors.length }}
                        </span>
                    </div>
                    <div v-if="isPolling" class="text-muted-foreground ml-2 flex items-center gap-1.5 text-xs">
                        <div class="h-3 w-3 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"></div>
                        <span>Syncing...</span>
                    </div>
                </div>

                <Button
                    size="sm"
                    variant="outline"
                    @click="fetchPinnedMonitors(false)"
                    :disabled="loading || isPolling"
                    class="h-8 gap-1.5 text-xs font-medium"
                >
                    <RefreshCw class="h-3.5 w-3.5" :class="refreshIconClass" />
                    <span>Refresh</span>
                </Button>
            </CardTitle>
        </CardHeader>
        <CardContent class="pt-4">
            <!-- Search / Filter Summary -->
            <div
                v-if="!loading && !error && (props.searchQuery || props.statusFilter !== 'all')"
                class="text-muted-foreground mb-3 flex items-center justify-between text-xs"
            >
                <span>Showing {{ filteredMonitors.length }} pinned monitor<span v-if="filteredMonitors.length !== 1">s</span></span>
            </div>

            <!-- Skeleton Loaders for Initial Load -->
            <div v-if="loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="i in 3" :key="i" class="border-border/60 bg-muted/20 flex animate-pulse flex-col gap-3 rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="bg-muted h-7 w-7 rounded-lg"></div>
                            <div class="space-y-1.5">
                                <div class="bg-muted h-3.5 w-28 rounded"></div>
                                <div class="bg-muted/60 h-2.5 w-36 rounded"></div>
                            </div>
                        </div>
                        <div class="bg-muted h-5 w-16 rounded-full"></div>
                    </div>
                    <div class="space-y-1.5 pt-2">
                        <div class="bg-muted h-2.5 w-full rounded"></div>
                        <div class="bg-muted h-1.5 w-full rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="border-destructive/20 bg-destructive/10 text-destructive rounded-xl border p-6 text-center text-sm">
                <p class="font-medium">{{ error }}</p>
                <Button size="sm" variant="outline" class="mt-3" @click="fetchPinnedMonitors(true)">Try Again</Button>
            </div>

            <!-- Empty State: No Pinned Monitors -->
            <div v-else-if="pinnedMonitors.length === 0" class="border-border/80 rounded-xl border border-dashed py-10 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20">
                        <Bookmark class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-foreground text-sm font-semibold">No pinned monitors</h4>
                        <p class="text-muted-foreground mt-1 text-xs">
                            Click the bookmark icon on any monitor card to pin it at the top for quick access.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty State: Filter / Search Match None -->
            <div v-else-if="filteredMonitors.length === 0" class="border-border/80 rounded-xl border border-dashed py-10 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                    <Search class="text-muted-foreground/60 h-8 w-8" />
                    <p class="text-foreground text-sm font-medium">No pinned monitors match your filter</p>
                    <p class="text-muted-foreground text-xs">Try clearing your search query or selecting a different status.</p>
                </div>
            </div>

            <!-- Monitor Grid -->
            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <MonitorCard
                    v-for="monitor in filteredMonitors"
                    :key="monitor.id"
                    :monitor="monitor"
                    type="private"
                    :is-pinned="true"
                    :on-toggle-pin="handleTogglePin"
                    :on-toggle-active="toggleMonitorActive"
                    :toggling-monitors="togglingMonitors"
                    :loading-monitors="loadingMonitors"
                    :show-subscribe-button="false"
                    :show-toggle-button="true"
                    :show-pin-button="true"
                    :show-uptime-percentage="true"
                    :show-certificate-status="true"
                    :show-last-checked="true"
                />
            </div>

            <!-- Load More Action -->
            <div
                v-if="hasMorePages && !loading && !error && (!props.searchQuery || props.searchQuery.trim().length < 3)"
                class="mt-6 flex flex-col items-center gap-2"
            >
                <Button variant="outline" @click="loadMore" :disabled="loadingMore" class="gap-2">
                    <ChevronDown class="h-4 w-4" :class="loadingMore ? 'animate-spin' : ''" />
                    <span v-if="loadingMore">Loading more...</span>
                    <span v-else>Load More Pinned Monitors ({{ pinnedMonitors.length }} of {{ totalMonitors }})</span>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
