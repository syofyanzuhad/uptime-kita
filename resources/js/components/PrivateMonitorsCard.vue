<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import type { Monitor } from '@/types/monitor';
import { Link, usePage, router } from '@inertiajs/vue3';
import type { SharedData } from '@/types';
import MonitorGrid from './MonitorGrid.vue';

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

const privateMonitors = ref<Monitor[]>([]);
const loading = ref(true);
const isPolling = ref(false);
const error = ref<string | null>(null);
// const pollingInterval = ref<number | null>(null);

// Toggle active state
const togglingMonitors = ref<Set<number>>(new Set());

// Pagination state
const currentPage = ref(1);
const hasMorePages = ref(false);
const loadingMore = ref(false);
const totalMonitors = ref(0);
const showingFrom = ref(0);
const showingTo = ref(0);

const pinnedMonitors = ref<Set<number>>(new Set());

const page = usePage<SharedData>();

// Check if user is authenticated using Inertia's auth props
const isAuthenticated = computed(() => {
    return !!page.props.auth.user;
});

const refreshIconClass = computed(() => {
    return loading.value || isPolling.value ? 'animate-spin' : '';
});

const filteredMonitors = computed(() => {
    if (!privateMonitors.value || privateMonitors.value.length === 0) {
        return [];
    }
    let monitors = privateMonitors.value;
    // Filter by status
    if (props.statusFilter === 'up' || props.statusFilter === 'down') {
        monitors = monitors.filter(monitor => monitor.uptime_status === props.statusFilter);
    } else if (props.statusFilter === 'globally_enabled') {
        // Filter for globally enabled monitors (uptime_check_enabled is true)
        monitors = monitors.filter(monitor => monitor.uptime_check_enabled);
    } else if (props.statusFilter === 'globally_disabled') {
        // Filter for globally disabled monitors (uptime_check_enabled is false)
        monitors = monitors.filter(monitor => !monitor.uptime_check_enabled);
    }
    // Filter by search query
    if (props.searchQuery && props.searchQuery.trim().length >= 3) {
        const query = props.searchQuery.toLowerCase().trim();
        monitors = monitors.filter(monitor => {
            const domain = getDomainFromUrl(monitor.url).toLowerCase();
            const url = monitor.url.toLowerCase();
            return domain.includes(query) || url.includes(query);
        });
    }
    return monitors;
});

const sortedMonitors = computed(() => {
    return [...filteredMonitors.value].sort((a, b) => {
        const aPinned = pinnedMonitors.value.has(a.id);
        const bPinned = pinnedMonitors.value.has(b.id);
        if (aPinned && !bPinned) return -1;
        if (!aPinned && bPinned) return 1;
        return 0;
    });
});

const togglePin = (monitorId: number) => {
    if (pinnedMonitors.value.has(monitorId)) {
        pinnedMonitors.value.delete(monitorId);
    } else {
        pinnedMonitors.value.add(monitorId);
    }
};

const isPinned = (monitorId: number) => {
    return pinnedMonitors.value.has(monitorId);
};

const fetchPrivateMonitors = async (isInitialLoad = false, page = 1) => {
    try {
        if (isInitialLoad) {
            loading.value = true;
            currentPage.value = 1;
        } else if (page > 1) {
            loadingMore.value = true;
        } else {
            isPolling.value = true;
        }

        // Add search query and status filter to request if present
        const params = new URLSearchParams();
        params.append('page', String(page));
        if (props.searchQuery && props.searchQuery.trim().length >= 3) {
            params.append('search', props.searchQuery.trim());
        }
        if (props.statusFilter !== 'all') {
            params.append('status_filter', props.statusFilter);
        }
        const response = await fetch(`/private-monitors?${params.toString()}`);
        if (!response.ok) {
            throw new Error('Failed to fetch private monitors');
        }

        const result = await response.json();

        if (isInitialLoad || page === 1) {
            privateMonitors.value = result.data;
        } else {
            // Append new monitors to existing ones
            privateMonitors.value = [...privateMonitors.value, ...result.data];
        }

        // Update pagination state using meta from MonitorResource
        hasMorePages.value = result.meta.current_page < result.meta.last_page;
        totalMonitors.value = result.meta.total;
        showingFrom.value = result.meta.from || 0;
        showingTo.value = result.meta.to || 0;
        currentPage.value = result.meta.current_page;

        error.value = null;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loading.value = false;
        isPolling.value = false;
        loadingMore.value = false;
    }
};

// Watch for searchQuery and statusFilter changes and refetch
watch([() => props.searchQuery, () => props.statusFilter], ([newQuery, newFilter], [oldQuery, oldFilter]) => {
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
        fetchPrivateMonitors(true, 1);
    }
});

const loadMore = async () => {
    if (hasMorePages.value && !loadingMore.value) {
        await fetchPrivateMonitors(false, currentPage.value + 1);
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'up':
            return 'checkCircle';
        case 'down':
            return 'xCircle';
        default:
            return 'clock';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'up':
            return 'Online';
        case 'down':
            return 'Offline';
        default:
            return 'Checking...';
    }
};

const getDomainFromUrl = (url: string) => {
    try {
        const domain = new URL(url).hostname;
        return domain.replace('www.', '');
    } catch {
        return url;
    }
};

const formatUptimePercentage = (percentage: number) => {
    return percentage.toFixed(1);
};

const getUptimePercentageColor = (percentage: number) => {
    if (percentage >= 99.5) return 'text-green-600 dark:text-green-400';
    if (percentage >= 95) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const openMonitorUrl = (url: string) => {
    window.open(url, '_blank');
};

const toggleActive = async (monitorId: number) => {
    if (!isAuthenticated.value) {
        // Redirect to login if not authenticated
        window.location.href = '/login';
        return;
    }

    try {
        togglingMonitors.value.add(monitorId);

        router.post(
            route('monitor.toggle-active', monitorId),
            {
                _token: page.props.csrf_token as string,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Update the monitor's uptime_check_enabled status
                    const monitor = privateMonitors.value.find(m => m.id === monitorId);
                    if (monitor) {
                        monitor.uptime_check_enabled = !monitor.uptime_check_enabled;
                    }
                },
                onError: () => {
                    alert('Terjadi kesalahan saat mengubah status monitor');
                },
                onFinish: () => {
                    togglingMonitors.value.delete(monitorId);
                }
            }
        );
    } catch {
        alert('Terjadi kesalahan saat mengubah status monitor');
        togglingMonitors.value.delete(monitorId);
    }
};

onMounted(() => {
    fetchPrivateMonitors(true);
    // pollingInterval.value = setInterval(() => {
    //     fetchPrivateMonitors(false, 1); // Polling update - always fetch first page
    // }, 60000);
});

onUnmounted(() => {
    // if (pollingInterval.value) {
    //     clearInterval(pollingInterval.value);
    // }
});
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Icon name="lock" class="text-yellow-500" />
                    Private Monitors
                    <div v-if="isPolling" class="flex items-center gap-1 ml-2">
                        <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-yellow-500"></div>
                        <span class="text-xs text-gray-500">Updating...</span>
                    </div>
                    <Link
                        :href="route('monitor.create')"
                        class="flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-lg transition-colors font-medium ml-2"
                        title="Add Monitor"
                    >
                        <Icon name="plus" size="16" />
                        Add Monitor
                    </Link>
                </div>
                <button
                    @click="fetchPrivateMonitors(false)"
                    :disabled="loading || isPolling"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Refresh monitors"
                >
                    <Icon
                        name="refresh-cw"
                        :class="refreshIconClass"
                        size="16"
                    />
                    Refresh
                </button>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                <template v-if="!loading && !error">
                    <template v-if="props.searchQuery && props.searchQuery.trim().length >= 3">
                        <!-- Search results info -->
                        <span v-if="filteredMonitors.length === 1">
                            Found 1 monitor
                        </span>
                        <span v-else>
                            Found {{ filteredMonitors.length }} monitors
                        </span>
                        <span v-if="totalMonitors !== filteredMonitors.length">
                            from {{ totalMonitors }} total monitors
                        </span>
                    </template>
                    <template v-else>
                        <!-- Regular pagination info -->
                        <template v-if="totalMonitors > 0">
                            Showing {{ showingFrom }} to {{ showingTo }} of {{ totalMonitors }}
                            monitor<span v-if="totalMonitors !== 1">s</span>
                            <span v-if="hasMorePages"> ({{ privateMonitors.length }} loaded)</span>
                        </template>
                        <template v-else>
                            No monitors found
                        </template>
                    </template>
                </template>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-500"></div>
            </div>
            <div v-else-if="error" class="text-center py-8 text-red-500">
                {{ error }}
            </div>
            <div v-else-if="privateMonitors.length === 0" class="text-center py-8 text-gray-500">
                No private monitors available
            </div>
            <div v-else-if="props.searchQuery && props.searchQuery.trim().length >= 3 && filteredMonitors.length === 0" class="text-center py-8 text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <Icon name="search" class="h-8 w-8 text-gray-400" />
                    <p>No monitors found for "{{ props.searchQuery }}"</p>
                    <p class="text-sm">Try different keywords</p>
                </div>
            </div>
            <MonitorGrid
                :monitors="sortedMonitors"
                type="private"
                :pinned-monitors="pinnedMonitors"
                :on-toggle-pin="togglePin"
                :on-toggle-active="toggleActive"
                :toggling-monitors="togglingMonitors"
                :show-subscribe-button="false"
                :show-toggle-button="true"
                :show-pin-button="true"
                :show-uptime-percentage="true"
                :show-certificate-status="true"
                :show-last-checked="true"
            />

            <!-- Load More Button -->
            <div v-if="hasMorePages && !loading && !error && (!props.searchQuery || props.searchQuery.trim().length < 3)" class="mt-6 text-center">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="flex items-center gap-2 px-6 py-3 bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                >
                    <Icon
                        name="arrow-down"
                        :class="loadingMore ? 'animate-spin' : ''"
                        size="16"
                    />
                    <span v-if="loadingMore">Loading...</span>
                    <span v-else>Load More Monitors</span>
                </button>
            </div>

            <!-- Loading More Indicator -->
            <div v-if="loadingMore" class="mt-4 text-center">
                <div class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-yellow-500"></div>
                    Loading more monitors...
                </div>
            </div>
        </CardContent>
    </Card>
</template>
