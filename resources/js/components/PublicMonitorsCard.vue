<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import type { Monitor } from '@/types/monitor';
import { usePage, router } from '@inertiajs/vue3';
import type { SharedData } from '@/types';
import MonitorGrid from './MonitorGrid.vue';
import { useBookmarks } from '@/composables/useBookmarks';

interface Props {
    searchQuery?: string;
    statusFilter?: 'all' | 'up' | 'down' | 'unsubscribed' | 'globally_enabled' | 'globally_disabled';
    allCount?: number;
    onlineCount?: number;
    offlineCount?: number;
    unsubscribedCount?: number;
    enabledCount?: number;
    disabledCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    searchQuery: '',
    statusFilter: 'all',
    allCount: 0,
    onlineCount: 0,
    offlineCount: 0,
    unsubscribedCount: 0,
    enabledCount: 0,
    disabledCount: 0,
});

const publicMonitors = ref<Monitor[]>([]);
const loading = ref(true);
const isPolling = ref(false);
const error = ref<string | null>(null);
// const pollingInterval = ref<number | null>(null);
const subscribingMonitors = ref<Set<number>>(new Set());
const unsubscribingMonitors = ref<Set<number>>(new Set());

// Toggle active state
const togglingMonitors = ref<Set<number>>(new Set());

// Pagination state
const currentPage = ref(1);
const hasMorePages = ref(false);
const loadingMore = ref(false);
const totalMonitors = ref(0);
const showingFrom = ref(0);
const showingTo = ref(0);

const { pinnedMonitors, isPinned, togglePin, loadingMonitors, initialize } = useBookmarks();

const page = usePage<SharedData>();

// Check if user is authenticated using Inertia's auth props
const isAuthenticated = computed(() => {
    return !!page.props.auth.user;
});

const refreshIconClass = computed(() => {
    return loading.value || isPolling.value ? 'animate-spin' : '';
});

// Filter monitors based on search query and status filter
const filteredMonitors = computed(() => {
    let monitors = publicMonitors.value;
    // Filter by status
    if (props.statusFilter === 'up' || props.statusFilter === 'down') {
        monitors = monitors.filter(monitor => monitor.uptime_status === props.statusFilter);
    } else if (props.statusFilter === 'unsubscribed') {
        monitors = monitors.filter(monitor => !monitor.is_subscribed);
    } else if (props.statusFilter === 'globally_enabled') {
        // Filter for globally enabled monitors (uptime_check_enabled is true)
        monitors = monitors.filter(monitor => monitor.uptime_check_enabled);
    } else if (props.statusFilter === 'globally_disabled') {
        // Filter for globally disabled monitors (uptime_check_enabled is false)
        monitors = monitors.filter(monitor => !monitor.uptime_check_enabled);
    }
    // Remove client-side search filter here
    return monitors;
});

// Sort monitors to show pinned ones first
const sortedMonitors = computed(() => {
    return [...filteredMonitors.value].sort((a, b) => {
        const aPinned = isPinned(a.id);
        const bPinned = isPinned(b.id);

        if (aPinned && !bPinned) return -1;
        if (!aPinned && bPinned) return 1;
        return 0;
    });
});

const handleTogglePin = async (monitorId: number) => {
    try {
        await togglePin(monitorId);
    } catch (error) {
        console.error('Failed to toggle pin:', error);
    }
};



const fetchPublicMonitors = async (isInitialLoad = false, page = 1) => {
    try {
        if (isInitialLoad) {
            loading.value = true;
            currentPage.value = 1;
        } else if (page > 1) {
            loadingMore.value = true;
        } else {
            isPolling.value = true;
        }

        const params = new URLSearchParams();
        params.append('page', String(page));
        if (props.searchQuery && props.searchQuery.trim().length >= 3) {
            params.append('search', props.searchQuery.trim());
        }
        if (props.statusFilter !== 'all') {
            params.append('status_filter', props.statusFilter);
        }
        const response = await fetch(`/public-monitors?${params.toString()}`);
        if (!response.ok) {
            throw new Error('Failed to fetch public monitors');
        }

        const result = await response.json();

        if (isInitialLoad || page === 1) {
            publicMonitors.value = result.data;
        } else {
            // Append new monitors to existing ones
            publicMonitors.value = [...publicMonitors.value, ...result.data];
        }

        // Update pagination state using meta from resource
        hasMorePages.value = result.meta.current_page < result.meta.last_page;
        totalMonitors.value = result.meta.total;
        showingFrom.value = publicMonitors.value.length > 0 ? 1 : 0;
        showingTo.value = publicMonitors.value.length;
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
        fetchPublicMonitors(true, 1);
    }
});

const loadMore = async () => {
    if (hasMorePages.value && !loadingMore.value) {
        await fetchPublicMonitors(false, currentPage.value + 1);
    }
};

const subscribeToMonitor = async (monitorId: number) => {
    if (!isAuthenticated.value) {
        // Redirect to login if not authenticated
        window.location.href = '/login';
        return;
    }

    try {
        subscribingMonitors.value.add(monitorId);

        router.post(
            '/monitor/' + monitorId + '/subscribe',
            {
                _token: page.props.csrf_token as string,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Update the monitor's subscription status
                    const monitor = publicMonitors.value.find(m => m.id === monitorId);
                    if (monitor) {
                        monitor.is_subscribed = true;
                    }
                    alert('Berhasil berlangganan monitor');
                },
                onError: () => {
                    alert('Terjadi kesalahan saat berlangganan monitor');
                },
                onFinish: () => {
                    subscribingMonitors.value.delete(monitorId);
                }
            }
        );
    } catch {
        alert('Terjadi kesalahan saat berlangganan monitor');
    }
};

const unsubscribeFromMonitor = async (monitorId: number) => {
    if (!isAuthenticated.value) {
        // Redirect to login if not authenticated
        window.location.href = '/login';
        return;
    }

    try {
        unsubscribingMonitors.value.add(monitorId);

        router.post(
            '/monitor/' + monitorId + '/unsubscribe',
            {
                _token: page.props.csrf_token as string,
                _method: 'DELETE', // Use DELETE method for unsubscribe
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Update the monitor's subscription status
                    const monitor = publicMonitors.value.find(m => m.id === monitorId);
                    if (monitor) {
                        monitor.is_subscribed = false;
                    }
                    alert('Berhasil berhenti berlangganan monitor');
                },
                onError: () => {
                    alert('Terjadi kesalahan saat berhenti berlangganan monitor');
                },
                onFinish: () => {
                    unsubscribingMonitors.value.delete(monitorId);
                }
            }
        );
    } catch {
        alert('Terjadi kesalahan saat berhenti berlangganan monitor');
    }
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
            `/monitor/${monitorId}/toggle-active`,
            {
                _token: page.props.csrf_token as string,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Update the monitor's uptime_check_enabled status
                    const monitor = publicMonitors.value.find(m => m.id === monitorId);
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
    initialize();
    fetchPublicMonitors(true); // Initial load
    // pollingInterval.value = setInterval(() => {
    //     fetchPublicMonitors(false, 1); // Polling update - always fetch first page
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
                    <Icon name="globe" class="text-blue-500" />
                    Public Monitors
                    <div v-if="isPolling" class="flex items-center gap-1 ml-2">
                        <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-500"></div>
                        <span class="text-xs text-gray-500">Updating...</span>
                    </div>
                </div>
                <button
                    @click="fetchPublicMonitors(false)"
                    :disabled="loading || isPolling"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
                <template v-if="filteredMonitors.length">
                    Showing {{ filteredMonitors.length }} of
                    {{
                        props.statusFilter === 'all' ? props.allCount
                        : props.statusFilter === 'up' ? props.onlineCount
                        : props.statusFilter === 'down' ? props.offlineCount
                        : props.statusFilter === 'unsubscribed' ? props.unsubscribedCount
                        : props.statusFilter === 'globally_enabled' ? props.enabledCount
                        : props.statusFilter === 'globally_disabled' ? props.disabledCount
                        : props.allCount
                    }}
                    monitor<span v-if="filteredMonitors.length !== 1">s</span>
                </template>
                <template v-else>
                    No
                    {{
                        props.statusFilter === 'all' ? ''
                        : props.statusFilter === 'up' ? 'online'
                        : props.statusFilter === 'down' ? 'offline'
                        : props.statusFilter === 'unsubscribed' ? 'unsubscribed'
                        : props.statusFilter === 'globally_enabled' ? 'enabled'
                        : props.statusFilter === 'globally_disabled' ? 'disabled'
                        : ''
                    }}
                    monitors found.
                </template>
            </div>

            <!-- Search Results Counter -->
            <div v-if="props.searchQuery && !loading && !error" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                <span v-if="filteredMonitors.length === 1">
                    Ditemukan 1 monitor
                </span>
                <span v-else>
                    Ditemukan {{ filteredMonitors.length }} monitor
                </span>
                <span v-if="publicMonitors.length !== filteredMonitors.length">
                    dari {{ publicMonitors.length }} total monitor
                </span>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>

            <div v-else-if="error" class="text-center py-8 text-red-500">
                {{ error }}
            </div>

            <div v-else-if="publicMonitors.length === 0" class="text-center py-8 text-gray-500">
                No public monitors available
            </div>

            <div v-else-if="props.searchQuery && filteredMonitors.length === 0" class="text-center py-8 text-gray-500">
                <div class="flex flex-col items-center gap-2">
                    <Icon name="search" class="h-8 w-8 text-gray-400" />
                    <p>Tidak ada monitor yang ditemukan untuk "{{ props.searchQuery }}"</p>
                    <p class="text-sm">Coba kata kunci yang berbeda</p>
                </div>
            </div>

            <MonitorGrid
                :monitors="sortedMonitors"
                type="public"
                :pinned-monitors="pinnedMonitors"
                :on-toggle-pin="handleTogglePin"
                :on-toggle-active="toggleActive"
                :on-subscribe="subscribeToMonitor"
                :on-unsubscribe="unsubscribeFromMonitor"
                :toggling-monitors="togglingMonitors"
                :subscribing-monitors="subscribingMonitors"
                :unsubscribing-monitors="unsubscribingMonitors"
                :loading-monitors="loadingMonitors"
                :show-subscribe-button="true"
                :show-toggle-button="true"
                :show-pin-button="true"
                :show-uptime-percentage="true"
                :show-certificate-status="true"
                :show-last-checked="true"
            />

            <!-- Load More Button -->
            <div v-if="hasMorePages && !loading && !error && !props.searchQuery" class="mt-6 text-center">
                <Button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="flex items-center gap-2 px-6 py-3 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                >
                    <Icon
                        name="arrow-down"
                        :class="loadingMore ? 'animate-spin' : ''"
                        size="16"
                    />
                    <span v-if="loadingMore">Loading...</span>
                    <span v-else>Load More Monitors</span>
                </Button>
            </div>

            <!-- Loading More Indicator -->
            <div v-if="loadingMore" class="mt-4 text-center">
                <div class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                    Loading more monitors...
                </div>
            </div>
        </CardContent>
    </Card>
</template>
