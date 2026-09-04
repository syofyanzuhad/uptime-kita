<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useBookmarks } from '@/composables/useBookmarks';
import { usePollMode } from '@/composables/usePollMode';
import type { SharedData } from '@/types';
import type { Monitor } from '@/types/monitor';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Lock, Plus, RefreshCw, Search } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MonitorGrid from './MonitorGrid.vue';
import Button from './ui/button/Button.vue';

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

// Toggle active state
const togglingMonitors = ref<Set<number>>(new Set());

// Pagination state
const currentPage = ref(1);
const hasMorePages = ref(false);
const loadingMore = ref(false);
const totalMonitors = ref(0);
const showingFrom = ref(0);
const showingTo = ref(0);

const { pinnedMonitors, isPinned, togglePin, loadingMonitors, initialize, onPinChanged } = useBookmarks();

const page = usePage<SharedData>();

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
            privateMonitors.value = result.data || [];
        } else {
            privateMonitors.value = [...privateMonitors.value, ...(result.data || [])];
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
};

watch([() => props.searchQuery, () => props.statusFilter], ([newQuery, newFilter], [oldQuery, oldFilter]) => {
    if (newQuery !== oldQuery || newFilter !== oldFilter) {
        currentPage.value = 1;
        hasMorePages.value = false;
        showingFrom.value = 0;
        showingTo.value = 0;
        totalMonitors.value = 0;
    }

    if (newQuery.trim().length === 0 || newQuery.trim().length >= 3) {
        fetchPrivateMonitors(true, 1);
    }
});

const loadMore = async () => {
    if (hasMorePages.value && !loadingMore.value) {
        await fetchPrivateMonitors(false, currentPage.value + 1);
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

const toggleActive = async (monitorId: number) => {
    if (!isAuthenticated.value) {
        window.location.href = '/login';
        return;
    }

    try {
        togglingMonitors.value.add(monitorId);

        router.post(
            route('monitors.toggle-active', monitorId),
            {
                _token: page.props.csrf_token as string,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const monitor = privateMonitors.value.find((m) => m.id === monitorId);
                    if (monitor) {
                        monitor.uptime_check_enabled = !monitor.uptime_check_enabled;
                    }
                },
                onError: () => {
                    alert('Terjadi kesalahan saat mengubah status monitor');
                },
                onFinish: () => {
                    togglingMonitors.value.delete(monitorId);
                },
            },
        );
    } catch {
        alert('Terjadi kesalahan saat mengubah status monitor');
        togglingMonitors.value.delete(monitorId);
    }
};

const { isAutoPolling } = usePollMode();

let pollingInterval: number | null = null;
let cleanupPinCallback: (() => void) | null = null;

function startPolling() {
    if (!isAutoPolling.value || pollingInterval) return;
    pollingInterval = window.setInterval(() => {
        if (!loading.value && !loadingMore.value) {
            fetchPrivateMonitors(false, 1);
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
    initialize();
    fetchPrivateMonitors(true);
    startPolling();

    cleanupPinCallback = onPinChanged(() => {
        fetchPrivateMonitors(false, 1);
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
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-500 dark:bg-indigo-500/20">
                        <Lock class="h-4 w-4" />
                    </div>
                    <div>
                        <span class="text-base font-bold text-gray-900 dark:text-white">Private Monitors</span>
                        <span
                            v-if="!loading && privateMonitors.length > 0"
                            class="ml-2 inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300"
                        >
                            {{ filteredMonitors.length }}
                        </span>
                    </div>
                    <div v-if="isPolling" class="text-muted-foreground ml-2 flex items-center gap-1.5 text-xs">
                        <div class="h-3 w-3 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></div>
                        <span>Syncing...</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('monitors.create')"
                        class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-8 items-center gap-1.5 rounded-lg px-3 text-xs font-medium shadow-xs transition-colors"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        <span>Add Monitor</span>
                    </Link>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="fetchPrivateMonitors(false)"
                        :disabled="loading || isPolling"
                        class="h-8 gap-1.5 text-xs font-medium"
                    >
                        <RefreshCw class="h-3.5 w-3.5" :class="refreshIconClass" />
                        <span>Refresh</span>
                    </Button>
                </div>
            </CardTitle>
        </CardHeader>
        <CardContent class="pt-4">
            <!-- Search / Filter Summary -->
            <div
                v-if="!loading && !error && (props.searchQuery || props.statusFilter !== 'all')"
                class="text-muted-foreground mb-3 flex items-center justify-between text-xs"
            >
                <span>Showing {{ filteredMonitors.length }} private monitor<span v-if="filteredMonitors.length !== 1">s</span></span>
            </div>

            <!-- Skeleton Loaders -->
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
                <Button size="sm" variant="outline" class="mt-3" @click="fetchPrivateMonitors(true)">Try Again</Button>
            </div>

            <!-- Empty State: No Private Monitors -->
            <div v-else-if="privateMonitors.length === 0" class="border-border/80 rounded-xl border border-dashed py-10 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500 dark:bg-indigo-500/20">
                        <Lock class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-foreground text-sm font-semibold">No private monitors yet</h4>
                        <p class="text-muted-foreground mt-1 text-xs">Add private websites or API endpoints that only you and your team can view.</p>
                    </div>
                    <Link
                        :href="route('monitors.create')"
                        class="bg-primary text-primary-foreground hover:bg-primary/90 mt-2 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium shadow-xs"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        Create Your First Monitor
                    </Link>
                </div>
            </div>

            <!-- Empty State: Filter / Search Match None -->
            <div v-else-if="filteredMonitors.length === 0" class="border-border/80 rounded-xl border border-dashed py-10 text-center">
                <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                    <Search class="text-muted-foreground/60 h-8 w-8" />
                    <p class="text-foreground text-sm font-medium">No private monitors match your filter</p>
                    <p class="text-muted-foreground text-xs">Try adjusting your search terms or status filter.</p>
                </div>
            </div>

            <!-- Monitor Grid -->
            <MonitorGrid
                v-else
                :monitors="sortedMonitors"
                type="private"
                :pinned-monitors="pinnedMonitors"
                :on-toggle-pin="handleTogglePin"
                :on-toggle-active="toggleActive"
                :toggling-monitors="togglingMonitors"
                :loading-monitors="loadingMonitors"
                :show-subscribe-button="false"
                :show-toggle-button="true"
                :show-pin-button="true"
                :show-uptime-percentage="true"
                :show-certificate-status="true"
                :show-last-checked="true"
            />

            <!-- Load More Action -->
            <div
                v-if="hasMorePages && !loading && !error && (!props.searchQuery || props.searchQuery.trim().length < 3)"
                class="mt-6 flex flex-col items-center gap-2"
            >
                <Button variant="outline" @click="loadMore" :disabled="loadingMore" class="gap-2">
                    <ChevronDown class="h-4 w-4" :class="loadingMore ? 'animate-spin' : ''" />
                    <span v-if="loadingMore">Loading more...</span>
                    <span v-else>Load More Private Monitors ({{ privateMonitors.length }} of {{ totalMonitors }})</span>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
