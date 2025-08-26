<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useBookmarks } from '@/composables/useBookmarks';
import type { SharedData } from '@/types';
import type { Monitor } from '@/types/monitor';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MonitorCard from './MonitorCard.vue';

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
        // Filter for globally enabled monitors (uptime_check_enabled is true)
        monitors = monitors.filter((monitor) => monitor.uptime_check_enabled);
    } else if (props.statusFilter === 'globally_disabled') {
        // Filter for globally disabled monitors (uptime_check_enabled is false)
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

        // Add search query and status filter to request if present
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
            // Append new monitors to existing ones
            pinnedMonitors.value = [...pinnedMonitors.value, ...(result.data || [])];
        }

        // Update pagination state using meta from MonitorResource
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
        const response = await fetch(`/monitor/${monitorId}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (page.props as any).csrf_token,
            },
        });

        if (!response.ok) throw new Error('Failed to toggle monitor status');

        const result = await response.json();

        // Update the monitor in the list
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
        // The refresh will happen automatically via the onPinChanged callback
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
        // Reset pagination state when search or filter changes
        if (newQuery !== oldQuery || newFilter !== oldFilter) {
            currentPage.value = 1;
            hasMorePages.value = false;
            showingFrom.value = 0;
            showingTo.value = 0;
            totalMonitors.value = 0;
        }

        // Only search if 3+ chars or empty (reset)
        if (newQuery.trim().length === 0 || newQuery.trim().length >= 3) {
            fetchPinnedMonitors(true, 1);
        }
    },
    { deep: true },
);

// Polling for updates and cleanup functions
let pollingInterval: number | null = null;
let cleanupPinCallback: (() => void) | null = null;

function startPolling() {
    if (pollingInterval) return;
    pollingInterval = window.setInterval(() => {
        if (!loading.value && !loadingMore.value) {
            fetchPinnedMonitors(false, 1); // Polling update - always fetch first page
        }
    }, 60000); // Poll every 60 seconds
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

    // Register refresh callback for when pins change
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
    <Card class="w-full">
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Icon name="bookmark" class="text-amber-500" />
                    Pinned Monitors
                    <div v-if="isPolling" class="ml-2 flex items-center gap-1">
                        <div class="h-3 w-3 animate-spin rounded-full border-b-2 border-amber-500"></div>
                        <span class="text-xs text-gray-500">Updating...</span>
                    </div>
                </div>
                <button
                    @click="fetchPinnedMonitors(false)"
                    :disabled="loading || isPolling"
                    class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-1.5 text-sm text-amber-600 transition-colors hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50"
                    title="Refresh monitors"
                >
                    <Icon name="refresh-cw" :class="refreshIconClass" size="16" />
                    Refresh
                </button>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                <template v-if="!loading && !error">
                    <template v-if="props.searchQuery && props.searchQuery.trim().length >= 3">
                        <!-- Search results info -->
                        <span v-if="filteredMonitors.length === 1"> Found 1 pinned monitor </span>
                        <span v-else> Found {{ filteredMonitors.length }} pinned monitors </span>
                        <span v-if="totalMonitors !== filteredMonitors.length"> from {{ totalMonitors }} total pinned monitors </span>
                    </template>
                    <template v-else>
                        <!-- Regular pagination info -->
                        <template v-if="totalMonitors > 0">
                            Showing {{ showingFrom }} to {{ showingTo }} of {{ totalMonitors }} pinned monitor<span v-if="totalMonitors !== 1"
                                >s</span
                            >
                            <span v-if="hasMorePages"> ({{ pinnedMonitors.length }} loaded)</span>
                        </template>
                        <template v-else> No pinned monitors found </template>
                    </template>
                </template>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-amber-500"></div>
            </div>
            <div v-else-if="error" class="py-8 text-center text-red-500">
                {{ error }}
            </div>
            <div v-else-if="pinnedMonitors.length === 0" class="py-8 text-center text-gray-500">
                <div class="flex flex-col items-center gap-4">
                    <Icon name="bookmark" class="h-12 w-12 text-gray-400" />
                    <div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-600 dark:text-gray-300">No Pinned Monitors</h3>
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Pin important monitors to keep them at the top of your dashboard.</p>
                        <Link
                            :href="route('monitor.create')"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                        >
                            <Icon name="plus" class="h-4 w-4" />
                            Create Your First Monitor
                        </Link>
                    </div>
                </div>
            </div>
            <div
                v-else-if="props.searchQuery && props.searchQuery.trim().length >= 3 && filteredMonitors.length === 0"
                class="py-8 text-center text-gray-500"
            >
                <div class="flex flex-col items-center gap-2">
                    <Icon name="search" class="h-8 w-8 text-gray-400" />
                    <p>No pinned monitors found for "{{ props.searchQuery }}"</p>
                    <p class="text-sm">Try different keywords</p>
                </div>
            </div>
            <div v-else :class="`grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3`">
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

            <!-- Load More Button -->
            <div v-if="hasMorePages && !loading && !error && (!props.searchQuery || props.searchQuery.trim().length < 3)" class="mt-6 text-center">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="flex items-center gap-2 rounded-lg bg-amber-50 px-6 py-3 font-medium text-amber-600 transition-colors hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50"
                >
                    <Icon name="arrow-down" :class="loadingMore ? 'animate-spin' : ''" size="16" />
                    <span v-if="loadingMore">Loading...</span>
                    <span v-else>Load More Monitors</span>
                </button>
            </div>

            <!-- Loading More Indicator -->
            <div v-if="loadingMore" class="mt-4 text-center">
                <div class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-amber-500"></div>
                    Loading more pinned monitors...
                </div>
            </div>
        </CardContent>
    </Card>
</template>
